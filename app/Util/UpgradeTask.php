<?php
declare(strict_types=1);

namespace App\Util;

/**
 * 升级任务状态机 + 文件持久化 + 同进程后台 worker。
 *
 * 设计要点：
 * - 只允许一个并发任务（state.json 全局单例）。
 * - state.json 用 rename 原子写，避免 worker 写一半被 status API 读到。
 * - worker 用 fastcgi_finish_request + ignore_user_abort 续跑，浏览器关掉也不影响。
 * - phase 进度按阶段权重累加，UI 直接显示 0-100。
 * - failed 时保留 phase / error / 已下载 zip，给用户"继续 / 回滚 / 丢弃"三选一。
 */
class UpgradeTask
{
    public const STATUS_QUEUED  = 'queued';
    public const STATUS_RUNNING = 'running';
    public const STATUS_DONE    = 'done';
    public const STATUS_FAILED  = 'failed';

    public const PHASE_PREPARE  = 'prepare';
    public const PHASE_DOWNLOAD = 'download';
    public const PHASE_EXTRACT  = 'extract';
    public const PHASE_BACKUP   = 'backup';
    public const PHASE_COPY     = 'copy';
    public const PHASE_MIGRATE  = 'migrate';
    public const PHASE_FINALIZE = 'finalize';
    public const PHASE_DONE     = 'done';

    /** @var array<string, int> 各阶段在总进度中的权重（合计 100） */
    public const PHASE_WEIGHT = [
        self::PHASE_PREPARE  => 1,
        self::PHASE_DOWNLOAD => 40,
        self::PHASE_EXTRACT  => 10,
        self::PHASE_BACKUP   => 10,
        self::PHASE_COPY     => 30,
        self::PHASE_MIGRATE  => 5,
        self::PHASE_FINALIZE => 4,
    ];

    public const PHASE_LABEL = [
        self::PHASE_PREPARE  => '准备升级环境',
        self::PHASE_DOWNLOAD => '下载升级包',
        self::PHASE_EXTRACT  => '解压升级包',
        self::PHASE_BACKUP   => '备份当前关键文件',
        self::PHASE_COPY     => '覆盖程序文件',
        self::PHASE_MIGRATE  => '执行数据库迁移',
        self::PHASE_FINALIZE => '写版本号与清缓存',
        self::PHASE_DONE     => '升级完成',
    ];

    /** 心跳超过此秒数视为可能僵死 */
    private const STALL_SECONDS = 120;

    /** done/failed 状态保留多久（秒），过期由下次 status 调用清理 */
    private const KEEP_SECONDS = 24 * 3600;

    private static function dir(): string
    {
        $dir = BASE_PATH . '/runtime/upgrade';
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }
        return $dir;
    }

    private static function statePath(): string
    {
        return self::dir() . '/state.json';
    }

    private static function logPath(): string
    {
        return self::dir() . '/last.log';
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function load(): ?array
    {
        $path = self::statePath();
        if (!is_file($path)) {
            return null;
        }
        $raw = (string)@file_get_contents($path);
        if ($raw === '') {
            return null;
        }
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            return null;
        }
        return $data;
    }

    /**
     * 原子写入：先写临时文件再 rename。
     * @param array<string, mixed> $data
     */
    private static function save(array $data): void
    {
        $data['updated_at'] = time();
        $path = self::statePath();
        $tmp  = $path . '.tmp';
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        if ($json === false) {
            return;
        }
        if (@file_put_contents($tmp, $json, LOCK_EX) === false) {
            return;
        }
        @rename($tmp, $path);
    }

    /**
     * 合并字段后写回。
     * @param array<string, mixed> $patch
     * @return array<string, mixed>|null
     */
    public static function patch(array $patch): ?array
    {
        $cur = self::load();
        if ($cur === null) {
            return null;
        }
        $merged = array_merge($cur, $patch);
        self::save($merged);
        return $merged;
    }

    public static function clear(): void
    {
        @unlink(self::statePath());
        @unlink(self::logPath());
    }

    /**
     * 追加一行日志（带时间戳）。
     */
    public static function appendLog(string $line): void
    {
        $line = '[' . date('H:i:s') . '] ' . trim($line) . "\n";
        @file_put_contents(self::logPath(), $line, FILE_APPEND | LOCK_EX);
    }

    public static function readLog(): string
    {
        $path = self::logPath();
        if (!is_file($path)) {
            return '';
        }
        $size = filesize($path) ?: 0;
        // 只取最后 8KB，避免页面卡
        if ($size > 8192) {
            $fp = @fopen($path, 'rb');
            if ($fp === false) {
                return '';
            }
            @fseek($fp, -8192, SEEK_END);
            $buf = (string)stream_get_contents($fp);
            @fclose($fp);
            return ltrim(substr($buf, (int)strpos($buf, "\n") + 1));
        }
        return (string)@file_get_contents($path);
    }

    /**
     * 创建一个新任务并落盘。如已有未结束任务，抛异常。
     *
     * @param array{tag?: string, version?: string, source: string, url?: string, local_path?: string} $params
     * @return array<string, mixed>
     */
    public static function create(array $params): array
    {
        $cur = self::load();
        if ($cur !== null && in_array($cur['status'] ?? '', [self::STATUS_QUEUED, self::STATUS_RUNNING], true)) {
            throw new \RuntimeException('已有一个升级任务正在进行，请先等其完成或丢弃');
        }
        // 清掉过期的 done/failed
        if ($cur !== null) {
            self::clear();
        }

        $now = time();
        $task = [
            'task_id'        => bin2hex(random_bytes(8)),
            'status'         => self::STATUS_QUEUED,
            'phase'          => self::PHASE_PREPARE,
            'phase_label'    => self::PHASE_LABEL[self::PHASE_PREPARE],
            'progress'       => 0,
            'source'         => (string)$params['source'],     // 'github' | 'local'
            'tag'            => (string)($params['tag'] ?? ''),
            'target_version' => (string)($params['version'] ?? ''),
            'url'            => (string)($params['url'] ?? ''),
            'local_path'     => (string)($params['local_path'] ?? ''),
            'zip_path'       => '',
            'work_dir'       => '',
            'backup_dir'     => '',
            'from_version'   => (string)((array)config('app'))['version'],
            'error'          => null,
            'failed_phase'   => null,
            'download_total' => 0,
            'download_done'  => 0,
            'copy_total'     => 0,
            'copy_done'      => 0,
            'created_at'     => $now,
            'updated_at'     => $now,
            'started_at'     => null,
            'finished_at'    => null,
            'pid'            => null,
        ];
        // 新任务前清理上次日志
        @unlink(self::logPath());
        self::save($task);
        self::appendLog("任务已创建 task_id={$task['task_id']} source={$task['source']} tag={$task['tag']}");
        return $task;
    }

    /**
     * 阶段切换 + 阶段内进度。
     * 总进度 = sum(已完成阶段权重) + 当前阶段权重 * 阶段内 ratio
     */
    public static function setPhase(string $phase, float $ratio = 0.0, ?string $label = null): void
    {
        $weights = self::PHASE_WEIGHT;
        $order = array_keys($weights);
        $idx = array_search($phase, $order, true);
        $done = 0;
        if ($idx !== false) {
            for ($i = 0; $i < $idx; $i++) {
                $done += $weights[$order[$i]];
            }
        }
        $curWeight = $weights[$phase] ?? 0;
        $progress = (int)floor($done + $curWeight * max(0.0, min(1.0, $ratio)));
        if ($progress > 100) {
            $progress = 100;
        }
        self::patch([
            'phase'       => $phase,
            'phase_label' => $label ?? (self::PHASE_LABEL[$phase] ?? $phase),
            'progress'    => $progress,
        ]);
    }

    /**
     * 给当前进行中的任务状态加上"是否僵死"派生字段，给 API 使用。
     */
    public static function withDerivedFields(?array $task): ?array
    {
        if ($task === null) {
            return null;
        }
        $now = time();
        $task['stalled'] = false;
        $task['log_tail'] = self::readLog();

        // 老的 done/failed 任务过期自动清理
        $updated = (int)($task['updated_at'] ?? 0);
        if (in_array($task['status'] ?? '', [self::STATUS_DONE, self::STATUS_FAILED], true)
            && $updated > 0 && $now - $updated > self::KEEP_SECONDS) {
            self::clear();
            return null;
        }

        if (($task['status'] ?? '') === self::STATUS_RUNNING) {
            if ($updated > 0 && $now - $updated > self::STALL_SECONDS) {
                $task['stalled'] = true;
            }
        }
        return $task;
    }

    /**
     * 调度 worker 在当前请求响应发回客户端之后续跑。
     *
     * 关键：
     * - register_shutdown_function 注册的回调在 PHP 脚本结束后执行
     * - 若 FPM，先 fastcgi_finish_request() 把响应刷给客户端再继续做重活
     * - ignore_user_abort(true) 让浏览器断开也不杀脚本
     * - set_time_limit(0) 关掉 PHP 自身超时（FPM 的 request_terminate_timeout 需另外设置）
     */
    public static function dispatch(string $taskId): void
    {
        register_shutdown_function(static function () use ($taskId) {
            @ignore_user_abort(true);
            @set_time_limit(0);
            if (function_exists('fastcgi_finish_request')) {
                @fastcgi_finish_request();
            } elseif (function_exists('litespeed_finish_request')) {
                @litespeed_finish_request();
            }
            try {
                // 直接实例化绑定类。Bind\App 本身无 DI 依赖，避免 worker 在 shutdown 阶段碰到
                // 已经被框架关闭的 Context 容器。
                (new \App\Service\Bind\App())->runUpgradeTask($taskId);
            } catch (\Throwable $e) {
                self::markFailed($e->getMessage());
            }
        });
    }

    public static function markRunning(): void
    {
        self::patch([
            'status'     => self::STATUS_RUNNING,
            'started_at' => time(),
            'pid'        => getmypid() ?: null,
            'error'      => null,
        ]);
    }

    public static function markDone(): void
    {
        self::patch([
            'status'      => self::STATUS_DONE,
            'phase'       => self::PHASE_DONE,
            'phase_label' => self::PHASE_LABEL[self::PHASE_DONE],
            'progress'    => 100,
            'finished_at' => time(),
            'error'       => null,
        ]);
    }

    public static function markFailed(string $error): void
    {
        $cur = self::load();
        $phase = $cur['phase'] ?? self::PHASE_PREPARE;
        self::patch([
            'status'       => self::STATUS_FAILED,
            'error'        => $error,
            'failed_phase' => $phase,
            'finished_at'  => time(),
        ]);
        self::appendLog('FAILED: ' . $error);
    }
}
