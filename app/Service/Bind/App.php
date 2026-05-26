<?php
declare(strict_types=1);

namespace App\Service\Bind;

use App\Util\File;
use App\Util\Github;
use App\Util\Http;
use App\Util\Migrator;
use App\Util\Opcache;
use App\Util\Permission;
use App\Util\Str;
use App\Util\UpgradeTask;
use App\Util\Zip;
use Kernel\Consts\Base;
use Kernel\Exception\JSONException;
use Kernel\Util\Context;
use Kernel\Util\Plugin;
use Kernel\Util\SQL;

/**
 * Class AppService
 * @package App\Service\Impl
 */
class App implements \App\Service\App
{
    /**
     * 旧异次元应用商店心跳已停用。保留方法签名兼容旧调用，仅维护 Install/Lock。
     * 主程序版本检查改用 Github::latestRelease()。
     */
    public function getVersions(): array
    {
        if (Context::get(Base::LOCK) == "") {
            file_put_contents(BASE_PATH . "/kernel/Install/Lock", Str::generateRandStr(32));
        }
        return [];
    }

    /**
     * 从 GitHub 插件仓库安装一个插件。$pluginId 参数为兼容旧前端调用保留，已不使用。
     *
     * @throws JSONException
     * @throws \ReflectionException
     */
    public function installPlugin(string $key, int $type, int $pluginId): void
    {
        $pluginPath = match ($type) {
            1 => BASE_PATH . "/app/Pay/{$key}/",
            2 => BASE_PATH . "/app/View/User/Theme/{$key}/",
            default => BASE_PATH . "/app/Plugin/{$key}/",
        };
        $entryFile = $type === 2 ? $pluginPath . "Config.php" : $pluginPath . "Config/Info.php";

        if (is_file($entryFile)) {
            throw new JSONException("该插件已被安装，请勿重复安装");
        }

        $item = \App\Util\GithubPluginRegistry::find($key, $type);
        if ($item === null) {
            throw new JSONException("在 GitHub 插件仓库中未找到 {$key} (type={$type})");
        }

        if (!is_dir($pluginPath) && !@mkdir($pluginPath, 0777, true) && !is_dir($pluginPath)) {
            throw new JSONException("无法创建插件目录");
        }

        try {
            \App\Util\GithubPluginDownloader::download($item, $pluginPath);
        } catch (\Throwable $e) {
            File::delDirectory($pluginPath);
            throw new JSONException("插件文件拉取失败：" . $e->getMessage());
        }

        if (!is_file($entryFile)) {
            File::delDirectory($pluginPath);
            throw new JSONException("插件目录下载后未找到入口配置文件，仓库结构可能有误");
        }

        // install.sql
        $installSql = $pluginPath . "install.sql";
        if (is_file($installSql)) {
            $database = (array)config("database");
            SQL::import(
                $installSql,
                (string)$database['host'],
                (string)$database['database'],
                (string)$database['username'],
                (string)$database['password'],
                (string)$database['prefix']
            );
        }

        if ($type === 0) {
            Plugin::runHookState($key, \Kernel\Annotation\Plugin::INSTALL);
        }

        Permission::makeTreeWritable($pluginPath);

        // 写商店追踪标记：用于区分"通过商店安装"和"老版本异次元附带 / 用户 SFTP 自己丢的预装文件"。
        // enrichPluginRow 根据这个 marker 把 install 字段渲染成 1（商店安装）或 2（本地预装/未追踪）。
        $marker = $pluginPath . '.faka-installed.json';
        @file_put_contents($marker, json_encode([
            'installed_at' => date('Y-m-d H:i:s'),
            'source'       => 'github_registry',
            'plugin_key'   => $key,
            'plugin_type'  => $type,
            'app_version'  => (string)((array)config('app'))['version'],
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        @chmod($marker, 0666);
    }

    /**
     * 从 GitHub 插件仓库更新一个已装插件。
     *
     * @throws JSONException
     * @throws \ReflectionException
     */
    public function updatePlugin(string $key, int $type, int $pluginId): void
    {
        $pluginPath = match ($type) {
            1 => BASE_PATH . "/app/Pay/{$key}/",
            2 => BASE_PATH . "/app/View/User/Theme/{$key}/",
            default => BASE_PATH . "/app/Plugin/{$key}/",
        };
        if (!is_dir($pluginPath)) {
            throw new JSONException("该插件还未安装，请先安装插件后再进行更新");
        }

        $item = \App\Util\GithubPluginRegistry::find($key, $type);
        if ($item === null) {
            throw new JSONException("在 GitHub 插件仓库中未找到 {$key} (type={$type})");
        }

        try {
            \App\Util\GithubPluginDownloader::download($item, $pluginPath);
        } catch (\Throwable $e) {
            throw new JSONException("插件文件拉取失败：" . $e->getMessage());
        }

        // update.sql
        $updateSql = $pluginPath . "update.sql";
        if (is_file($updateSql)) {
            $database = (array)config("database");
            SQL::import(
                $updateSql,
                (string)$database['host'],
                (string)$database['database'],
                (string)$database['username'],
                (string)$database['password'],
                (string)$database['prefix']
            );
        }

        if ($type === 0) {
            Plugin::runHookState($key, \Kernel\Annotation\Plugin::UPGRADE);
        } elseif ($type === 2) {
            // 清空模版缓存
            $viewDir = realpath(BASE_PATH . "/runtime/view/");
            if ($viewDir) {
                File::delDirectory($viewDir);
            }
        }

        foreach ([BASE_PATH . '/runtime/plugin/store.cache', BASE_PATH . '/runtime/plugin/update.cache', BASE_PATH . '/runtime/plugin/registry.cache'] as $f) {
            if (is_file($f)) {
                @unlink($f);
            }
        }

        Permission::makeTreeWritable($pluginPath);
    }

    /**
     * 卸载
     * @param string $key
     * @param int $type
     */
    public function uninstallPlugin(string $key, int $type): void
    {
        //默认位置，通用插件
        $pluginPath = BASE_PATH . "/app/Plugin/{$key}/";
        if ($type == 1) {
            //支付插件
            $pluginPath = BASE_PATH . "/app/Pay/{$key}/";
        } elseif ($type == 2) {
            //网站模板
            $pluginPath = BASE_PATH . "/app/View/User/Theme/{$key}/";
        }
        if (is_dir($pluginPath)) {
            //开始卸载
            File::delDirectory($pluginPath);
        }
    }

    /**
     * @throws JSONException
     */
    public function purchaseRecords(int $pluginId): array
    {
        // 插件全部免费、无购买记录
        return [];
    }

    /**
     * @throws JSONException
     */
    public function unbind(int $authId): array
    {
        throw new JSONException("应用商店账号体系已停用");
    }

    /**
     * 旧异次元商店升级路径已废弃。主程序升级请使用 updateFromZip()，
     * 由 Controller 的 githubUpdate / localUpdate 调用。
     *
     * @throws JSONException
     */
    public function update(): void
    {
        throw new JSONException("此入口已废弃，请使用 GitHub 升级流程（侧栏点版本号）");
    }

    /**
     * 通过本地 zip 升级主程序（GitHub release zip 或管理员手动上传的整包）。
     *
     * 流程：解压 -> 探测源根 -> 校验 -> 备份 -> 白名单覆盖 -> 跑迁移 -> 写版本号 -> 清缓存。
     *
     * @throws JSONException
     */
    public function updateFromZip(string $zipPath, string $targetVersion = ''): void
    {
        $targetVersion = trim($targetVersion);
        if (!is_file($zipPath)) {
            throw new JSONException("升级包不存在：{$zipPath}");
        }

        $work = BASE_PATH . "/kernel/Install/Update/local-" . time() . "-" . substr(md5((string)mt_rand()), 0, 6) . "/";
        if (!is_dir($work) && !mkdir($work, 0777, true) && !is_dir($work)) {
            throw new JSONException("无法创建临时目录：{$work}");
        }

        if (!Zip::unzip($zipPath, $work)) {
            File::delDirectory($work);
            throw new JSONException("解压升级包失败，请检查 zip 是否完整");
        }

        $srcRoot = $this->detectSourceRoot($work);
        if ($srcRoot === null) {
            File::delDirectory($work);
            throw new JSONException("升级包格式不正确：未找到 index.php / composer.json");
        }

        // 未指定版本号时，从 zip 内 config/app.php 自动识别
        if ($targetVersion === '') {
            $detected = self::detectVersionFromConfig($srcRoot . "/config/app.php");
            if ($detected === '') {
                File::delDirectory($work);
                throw new JSONException("无法从升级包的 config/app.php 中识别版本号，请手动指定");
            }
            $targetVersion = $detected;
        }

        if (!preg_match('/^[0-9A-Za-z._\-+]+$/', $targetVersion)) {
            File::delDirectory($work);
            throw new JSONException("非法的目标版本号：{$targetVersion}");
        }

        // 备份关键文件
        $this->backupCurrent();

        // 白名单覆盖
        $excludeRel = $this->buildExcludeList();
        try {
            self::safeCopy($srcRoot, BASE_PATH, $excludeRel);
        } catch (\Throwable $e) {
            File::delDirectory($work);
            throw new JSONException("文件覆盖失败：" . $e->getMessage());
        }

        // 数据库迁移
        $oldVersion = (string)((array)config("app"))['version'];
        try {
            Migrator::migrate($oldVersion, $targetVersion);
        } catch (\Throwable $e) {
            // 文件已覆盖，迁移失败时仍向用户清晰报错；版本号不写入，下次再触发可补跑
            File::delDirectory($work);
            throw new JSONException("数据库迁移失败：" . $e->getMessage());
        }

        // 写版本号
        $appCfg = BASE_PATH . "/config/app.php";
        setConfig(["version" => $targetVersion], $appCfg);
        Opcache::invalidate($appCfg);

        // 清缓存
        $viewDir = BASE_PATH . "/runtime/view";
        if (is_dir($viewDir)) {
            File::delDirectory($viewDir);
        }
        foreach ([BASE_PATH . '/runtime/plugin/store.cache', BASE_PATH . '/runtime/plugin/update.cache'] as $cacheFile) {
            if (is_file($cacheFile)) {
                @unlink($cacheFile);
            }
        }
        Opcache::reset();

        // 升级结束后自动给可写目录批量授权
        Permission::grantWritableDirs();

        // 清理工作目录与原 zip
        File::delDirectory($work);
        @unlink($zipPath);
    }

    /**
     * 任务化升级 worker。由 UpgradeTask::dispatch() 在响应发回客户端之后调度。
     *
     * 与 updateFromZip 的区别：
     * - 全程把进度写到 state.json，前端轮询拿到阶段/百分比/日志
     * - 每个阶段失败时 status=failed，保留 zip / work_dir / backup_dir，允许"继续"或"回滚"
     * - 重入安全：再次调用时根据 phase 跳过已经完成的阶段
     *
     * @throws \Throwable
     */
    public function runUpgradeTask(string $taskId): void
    {
        $task = UpgradeTask::load();
        if ($task === null || ($task['task_id'] ?? '') !== $taskId) {
            return;
        }
        if (in_array($task['status'] ?? '', [UpgradeTask::STATUS_DONE], true)) {
            return;
        }

        UpgradeTask::markRunning();
        UpgradeTask::appendLog("worker 启动 pid=" . (getmypid() ?: '?') . " phase=" . ($task['phase'] ?? ''));

        try {
            // 同一次 worker 内允许从任何阶段继续。各阶段方法自身做"已完成则跳过"判断。
            $task = $this->phaseDownload($task);
            $task = $this->phaseExtract($task);
            $task = $this->phaseBackup($task);
            $task = $this->phaseCopy($task);
            $task = $this->phaseMigrate($task);
            $this->phaseFinalize($task);

            UpgradeTask::markDone();
            UpgradeTask::appendLog('全部阶段完成');
        } catch (\Throwable $e) {
            UpgradeTask::markFailed($e->getMessage());
            throw $e;
        }
    }

    /**
     * 阶段 1：下载升级包（GitHub source / local source 都用同一份 zip_path 存盘）。
     */
    private function phaseDownload(array $task): array
    {
        $alreadyOk = !empty($task['zip_path']) && is_file($task['zip_path']) && filesize($task['zip_path']) > 0;
        if ($alreadyOk) {
            UpgradeTask::appendLog('phaseDownload: zip 已存在，跳过下载 ' . $task['zip_path']);
            UpgradeTask::setPhase(UpgradeTask::PHASE_DOWNLOAD, 1.0);
            return UpgradeTask::load() ?: $task;
        }

        UpgradeTask::setPhase(UpgradeTask::PHASE_DOWNLOAD, 0.0);

        // 本地上传分支：直接把已上传到 runtime 的 zip 复制/挪到 work zone
        if (($task['source'] ?? '') === 'local') {
            $src = BASE_PATH . (string)$task['local_path'];
            if (!is_file($src)) {
                throw new \RuntimeException("本地升级包不存在：{$task['local_path']}");
            }
            if (strtolower((string)pathinfo($src, PATHINFO_EXTENSION)) !== 'zip') {
                throw new \RuntimeException('仅支持 zip 升级包');
            }
            UpgradeTask::patch(['zip_path' => $src, 'download_total' => filesize($src), 'download_done' => filesize($src)]);
            UpgradeTask::setPhase(UpgradeTask::PHASE_DOWNLOAD, 1.0);
            UpgradeTask::appendLog('phaseDownload: 使用本地包 ' . $src);
            return UpgradeTask::load() ?: $task;
        }

        // GitHub 源：tag 必填，按需现拉
        $url = (string)$task['url'];
        if ($url === '') {
            // 没存 url 时按 tag 现拉一次（断点续传时常见）
            $tag = (string)$task['tag'];
            if ($tag === '') {
                throw new \RuntimeException('GitHub 升级缺少 tag');
            }
            $target = null;
            foreach (Github::listReleases() as $r) {
                if ((string)$r['tag'] === $tag || (string)$r['version'] === $tag) {
                    $target = $r;
                    break;
                }
            }
            if ($target === null) {
                $latest = Github::latestRelease();
                if ($latest !== null && ((string)$latest['tag'] === $tag || (string)$latest['version'] === $tag)) {
                    $target = $latest;
                }
            }
            if ($target === null) {
                throw new \RuntimeException("未在 GitHub 仓库中找到版本 {$tag}");
            }
            $url = Github::pickDownloadUrl($target);
            if ($url === '') {
                throw new \RuntimeException('该版本未提供可下载的 zip 资源');
            }
            UpgradeTask::patch(['url' => $url]);
        }

        $dir = BASE_PATH . '/kernel/Install/Update';
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }
        $tagSafe = preg_replace('/[^A-Za-z0-9._\-]/', '_', (string)($task['tag'] ?: 'src')) ?? 'src';
        $zipPath = $dir . '/github-' . $tagSafe . '-' . (int)$task['created_at'] . '.zip';

        // 进度回调：节流，避免每 16KB 写一次盘
        $lastWrite = 0.0;
        $onProgress = static function (int $downloaded, int $total) use (&$lastWrite) {
            $now = microtime(true);
            if ($now - $lastWrite < 0.5 && $downloaded !== $total) {
                return;
            }
            $lastWrite = $now;
            $ratio = $total > 0 ? max(0.0, min(1.0, $downloaded / $total)) : 0.0;
            UpgradeTask::patch(['download_done' => $downloaded, 'download_total' => $total]);
            UpgradeTask::setPhase(UpgradeTask::PHASE_DOWNLOAD, $ratio);
        };

        UpgradeTask::appendLog('phaseDownload: 开始下载 ' . $url);
        Http::downloadWithProgress($url, $zipPath, $onProgress);

        UpgradeTask::patch(['zip_path' => $zipPath]);
        UpgradeTask::setPhase(UpgradeTask::PHASE_DOWNLOAD, 1.0);
        UpgradeTask::appendLog('phaseDownload: 完成 ' . filesize($zipPath) . ' 字节');
        return UpgradeTask::load() ?: $task;
    }

    /**
     * 阶段 2：解压 zip 到临时工作目录 + 探测源根 + 确定目标版本号。
     */
    private function phaseExtract(array $task): array
    {
        if (!empty($task['work_dir']) && is_dir($task['work_dir']) && !empty($task['src_root']) && is_dir($task['src_root'])) {
            UpgradeTask::appendLog('phaseExtract: 已解压，跳过');
            UpgradeTask::setPhase(UpgradeTask::PHASE_EXTRACT, 1.0);
            return UpgradeTask::load() ?: $task;
        }

        UpgradeTask::setPhase(UpgradeTask::PHASE_EXTRACT, 0.0);
        $zipPath = (string)$task['zip_path'];
        if (!is_file($zipPath)) {
            throw new \RuntimeException("升级包丢失：{$zipPath}");
        }

        $work = BASE_PATH . '/kernel/Install/Update/work-' . (int)$task['created_at'] . '-' . substr(md5($task['task_id']), 0, 6) . '/';
        if (!is_dir($work) && !mkdir($work, 0777, true) && !is_dir($work)) {
            throw new \RuntimeException("无法创建临时目录：{$work}");
        }
        if (!Zip::unzip($zipPath, $work)) {
            throw new \RuntimeException('解压升级包失败，请检查 zip 是否完整');
        }
        UpgradeTask::setPhase(UpgradeTask::PHASE_EXTRACT, 0.6);

        $srcRoot = $this->detectSourceRoot($work);
        if ($srcRoot === null) {
            throw new \RuntimeException('升级包格式不正确：未找到 index.php / composer.json');
        }

        $target = (string)$task['target_version'];
        if ($target === '') {
            $target = self::detectVersionFromConfig($srcRoot . '/config/app.php');
            if ($target === '') {
                throw new \RuntimeException('无法从升级包的 config/app.php 中识别版本号，请手动指定');
            }
        }
        $target = Github::normalizeVersion($target);
        if (!preg_match('/^[0-9A-Za-z._\-+]+$/', $target)) {
            throw new \RuntimeException("非法的目标版本号：{$target}");
        }

        UpgradeTask::patch([
            'work_dir'       => $work,
            'src_root'       => $srcRoot,
            'target_version' => $target,
        ]);
        UpgradeTask::setPhase(UpgradeTask::PHASE_EXTRACT, 1.0);
        UpgradeTask::appendLog("phaseExtract: 完成 src_root={$srcRoot} target={$target}");
        return UpgradeTask::load() ?: $task;
    }

    /**
     * 阶段 3：备份关键文件到 kernel/Install/Backup/{stamp}/。
     */
    private function phaseBackup(array $task): array
    {
        if (!empty($task['backup_dir']) && is_dir($task['backup_dir'])) {
            UpgradeTask::appendLog('phaseBackup: 已备份，跳过');
            UpgradeTask::setPhase(UpgradeTask::PHASE_BACKUP, 1.0);
            return UpgradeTask::load() ?: $task;
        }
        UpgradeTask::setPhase(UpgradeTask::PHASE_BACKUP, 0.0);

        $backupRoot = BASE_PATH . '/kernel/Install/Backup';
        if (!is_dir($backupRoot)) {
            @mkdir($backupRoot, 0777, true);
        }
        $stamp = date('YmdHis');
        $dst = $backupRoot . '/' . $stamp;
        if (!@mkdir($dst, 0777, true) && !is_dir($dst)) {
            UpgradeTask::appendLog('phaseBackup: 备份目录创建失败，跳过备份继续升级');
            UpgradeTask::setPhase(UpgradeTask::PHASE_BACKUP, 1.0);
            return UpgradeTask::load() ?: $task;
        }

        $targets = [
            'app'             => true,
            'kernel'          => true,
            'config/app.php'  => false,
            'composer.json'   => false,
            'composer.lock'   => false,
            'index.php'       => false,
        ];
        $i = 0;
        $n = count($targets);
        foreach ($targets as $rel => $isDir) {
            $src = BASE_PATH . '/' . $rel;
            if (file_exists($src)) {
                $to = $dst . '/' . $rel;
                try {
                    @mkdir(dirname($to), 0777, true);
                    if ($isDir) {
                        File::copyDirectory($src, $to);
                    } else {
                        @copy($src, $to);
                    }
                } catch (\Throwable $e) {
                    UpgradeTask::appendLog('phaseBackup: 备份 ' . $rel . ' 失败 ' . $e->getMessage());
                }
            }
            $i++;
            UpgradeTask::setPhase(UpgradeTask::PHASE_BACKUP, $i / max(1, $n));
        }
        $this->trimOldBackups($backupRoot, 5);

        UpgradeTask::patch(['backup_dir' => $dst]);
        UpgradeTask::setPhase(UpgradeTask::PHASE_BACKUP, 1.0);
        UpgradeTask::appendLog("phaseBackup: 完成 {$dst}");
        return UpgradeTask::load() ?: $task;
    }

    /**
     * 阶段 4：覆盖文件。safeCopy 是幂等的（覆盖式），失败重跑安全。
     */
    private function phaseCopy(array $task): array
    {
        $srcRoot = (string)($task['src_root'] ?? '');
        if (!is_dir($srcRoot)) {
            throw new \RuntimeException('phaseCopy: src_root 已丢失，请重新下载并解压');
        }
        UpgradeTask::setPhase(UpgradeTask::PHASE_COPY, 0.0);

        $excludeRel = $this->buildExcludeList();
        $total = self::countSourceFiles($srcRoot, $excludeRel);
        $total = max(1, $total);
        UpgradeTask::patch(['copy_total' => $total, 'copy_done' => 0]);

        $done = 0;
        $lastWrite = 0.0;
        $onFile = static function (string $abs, string $rel) use (&$done, $total, &$lastWrite) {
            $done++;
            $now = microtime(true);
            if ($now - $lastWrite < 0.3 && $done !== $total) {
                return;
            }
            $lastWrite = $now;
            UpgradeTask::patch(['copy_done' => $done]);
            UpgradeTask::setPhase(UpgradeTask::PHASE_COPY, $done / $total);
        };

        try {
            self::safeCopy($srcRoot, BASE_PATH, $excludeRel, '', $onFile);
        } catch (\Throwable $e) {
            throw new \RuntimeException('文件覆盖失败：' . $e->getMessage(), 0, $e);
        }

        UpgradeTask::patch(['copy_done' => $total]);
        UpgradeTask::setPhase(UpgradeTask::PHASE_COPY, 1.0);
        UpgradeTask::appendLog("phaseCopy: 完成 {$total} 个文件");
        return UpgradeTask::load() ?: $task;
    }

    /**
     * 阶段 5：跑 pending migration。Migrator 自身有 applied 表去重，重跑安全。
     */
    private function phaseMigrate(array $task): array
    {
        UpgradeTask::setPhase(UpgradeTask::PHASE_MIGRATE, 0.0);
        $from = (string)$task['from_version'];
        $to = (string)$task['target_version'];

        Migrator::ensureTable($from);
        $pending = Migrator::pending($to);
        $n = count($pending);

        if ($n === 0) {
            UpgradeTask::setPhase(UpgradeTask::PHASE_MIGRATE, 1.0);
            UpgradeTask::appendLog('phaseMigrate: 无待执行迁移');
            return UpgradeTask::load() ?: $task;
        }
        UpgradeTask::appendLog("phaseMigrate: {$n} 个迁移待执行");

        foreach ($pending as $i => $item) {
            UpgradeTask::patch(['phase_label' => "执行迁移 {$item['version']}.sql"]);
            try {
                Migrator::apply([$item]);
            } catch (\Throwable $e) {
                throw new \RuntimeException("迁移 {$item['version']}.sql 失败：" . $e->getMessage(), 0, $e);
            }
            UpgradeTask::setPhase(UpgradeTask::PHASE_MIGRATE, ($i + 1) / $n);
            UpgradeTask::appendLog("phaseMigrate: {$item['version']}.sql ok");
        }
        return UpgradeTask::load() ?: $task;
    }

    /**
     * 阶段 6：写版本号 + 清缓存 + 清理工作目录。
     */
    private function phaseFinalize(array $task): void
    {
        UpgradeTask::setPhase(UpgradeTask::PHASE_FINALIZE, 0.0);
        $target = (string)$task['target_version'];
        $appCfg = BASE_PATH . '/config/app.php';
        setConfig(['version' => $target], $appCfg);
        Opcache::invalidate($appCfg);
        UpgradeTask::setPhase(UpgradeTask::PHASE_FINALIZE, 0.3);

        $viewDir = BASE_PATH . '/runtime/view';
        if (is_dir($viewDir)) {
            File::delDirectory($viewDir);
        }
        foreach ([BASE_PATH . '/runtime/plugin/store.cache', BASE_PATH . '/runtime/plugin/update.cache'] as $cacheFile) {
            if (is_file($cacheFile)) {
                @unlink($cacheFile);
            }
        }
        Opcache::reset();
        UpgradeTask::setPhase(UpgradeTask::PHASE_FINALIZE, 0.7);

        try {
            Permission::grantWritableDirs();
        } catch (\Throwable $e) {
            UpgradeTask::appendLog('phaseFinalize: grantWritableDirs 失败 ' . $e->getMessage());
        }

        // 清工作目录与下载 zip（失败也不影响升级成功）
        if (!empty($task['work_dir']) && is_dir($task['work_dir'])) {
            File::delDirectory($task['work_dir']);
        }
        if (!empty($task['zip_path']) && is_file($task['zip_path']) && ($task['source'] ?? '') === 'github') {
            @unlink($task['zip_path']);
        }
        UpgradeTask::setPhase(UpgradeTask::PHASE_FINALIZE, 1.0);
        UpgradeTask::appendLog('phaseFinalize: 完成 version=' . $target);
    }

    /**
     * 从备份目录把 PHP 文件回滚回去（不动数据库）。
     */
    public function rollbackFromBackup(string $backupDir): void
    {
        $abs = $backupDir;
        if (!str_starts_with(str_replace('\\', '/', $abs), '/')
            && !preg_match('/^[A-Za-z]:[\\\\\\/]/', $abs)) {
            $abs = BASE_PATH . '/' . ltrim($abs, '/\\');
        }
        if (!is_dir($abs)) {
            throw new \RuntimeException('备份目录不存在：' . $backupDir);
        }
        $targets = ['app', 'kernel', 'config/app.php', 'composer.json', 'composer.lock', 'index.php'];
        foreach ($targets as $rel) {
            $src = $abs . '/' . $rel;
            $dst = BASE_PATH . '/' . $rel;
            if (!file_exists($src)) {
                continue;
            }
            if (is_dir($src)) {
                File::copyDirectory($src, $dst);
            } else {
                @mkdir(dirname($dst), 0777, true);
                @copy($src, $dst);
                @chmod($dst, 0644);
                Opcache::invalidate($dst);
            }
        }
        Opcache::reset();
    }

    /**
     * 探测解压后的源根：优先当前目录，其次"只有一个子目录"时下钻一层（处理 GitHub zipball 包裹层）。
     */
    private function detectSourceRoot(string $work): ?string
    {
        $candidate = rtrim($work, "/\\");
        for ($i = 0; $i < 3; $i++) {
            if (is_file($candidate . "/index.php") && is_file($candidate . "/composer.json")) {
                return $candidate;
            }
            $children = array_values(array_filter((array)scandir($candidate) ?: [], fn($n) => is_string($n) && $n !== '.' && $n !== '..'));
            if (count($children) === 1 && is_dir($candidate . "/" . $children[0])) {
                $candidate .= "/" . $children[0];
                continue;
            }
            break;
        }
        return null;
    }

    /**
     * 备份当前关键文件到 kernel/Install/Backup/{timestamp}/，保留最近 5 份。
     */
    private function backupCurrent(): void
    {
        $backupRoot = BASE_PATH . "/kernel/Install/Backup";
        if (!is_dir($backupRoot)) {
            @mkdir($backupRoot, 0777, true);
        }
        $stamp = date("YmdHis");
        $dst = $backupRoot . "/" . $stamp;
        if (!@mkdir($dst, 0777, true) && !is_dir($dst)) {
            return; // 备份失败不阻断升级
        }
        $targets = [
            "app" => true,
            "kernel" => true,
            "config/app.php" => false,
            "composer.json" => false,
            "composer.lock" => false,
            "index.php" => false,
        ];
        foreach ($targets as $rel => $isDir) {
            $src = BASE_PATH . "/" . $rel;
            if (!file_exists($src)) {
                continue;
            }
            $to = $dst . "/" . $rel;
            try {
                if ($isDir) {
                    @mkdir(dirname($to), 0777, true);
                    File::copyDirectory($src, $to);
                } else {
                    @mkdir(dirname($to), 0777, true);
                    @copy($src, $to);
                }
            } catch (\Throwable $e) {
                // 单项备份失败不阻断
            }
        }
        $this->trimOldBackups($backupRoot, 5);
    }

    private function trimOldBackups(string $backupRoot, int $keep): void
    {
        if (!is_dir($backupRoot)) {
            return;
        }
        $items = array_values(array_filter((array)scandir($backupRoot) ?: [], fn($n) => is_string($n) && $n !== '.' && $n !== '..' && is_dir($backupRoot . "/" . $n)));
        sort($items);
        $excess = count($items) - $keep;
        if ($excess <= 0) {
            return;
        }
        for ($i = 0; $i < $excess; $i++) {
            File::delDirectory($backupRoot . "/" . $items[$i]);
        }
    }

    /**
     * 升级时需要保留的相对路径白名单。
     *
     * @return array<int, string>
     */
    private function buildExcludeList(): array
    {
        return [
            "runtime",
            "vendor",
            "config/database.php",
            "config/store.php",
            "kernel/Install/Lock",
            "kernel/Install/OS",
            "kernel/Install/Update",
            "kernel/Install/Backup",
            ".git",
            ".github",
            ".env",
        ];
    }

    /**
     * 按白名单递归覆盖：只覆盖/新增，不删除目标里多余的文件。
     *
     * @param array<int, string> $excludeRel
     * @param callable|null      $onFile 每写完一个文件回调 ($absPath, $relPath)
     */
    private static function safeCopy(string $src, string $dst, array $excludeRel, string $relBase = "", ?callable $onFile = null): void
    {
        $dir = opendir($src);
        if ($dir === false) {
            throw new \RuntimeException("无法打开目录：{$src}");
        }
        try {
            while (($name = readdir($dir)) !== false) {
                if ($name === '.' || $name === '..') {
                    continue;
                }
                $srcPath = $src . DIRECTORY_SEPARATOR . $name;
                $dstPath = $dst . DIRECTORY_SEPARATOR . $name;
                $rel = $relBase === '' ? $name : $relBase . '/' . $name;

                if (self::isExcluded($rel, $excludeRel)) {
                    continue;
                }

                if (is_dir($srcPath)) {
                    if (!is_dir($dstPath)) {
                        if (!@mkdir($dstPath, 0755, true) && !is_dir($dstPath)) {
                            throw new \RuntimeException("无法创建目录：{$dstPath}");
                        }
                    }
                    self::safeCopy($srcPath, $dstPath, $excludeRel, $rel, $onFile);
                } else {
                    if (!is_dir(dirname($dstPath))) {
                        @mkdir(dirname($dstPath), 0755, true);
                    }
                    if (!@copy($srcPath, $dstPath)) {
                        throw new \RuntimeException("无法写入文件：{$dstPath}");
                    }
                    @chmod($dstPath, 0644);
                    Opcache::invalidate($dstPath);
                    if ($onFile !== null) {
                        $onFile($dstPath, $rel);
                    }
                }
            }
        } finally {
            closedir($dir);
        }
    }

    /**
     * 预数源根下需要覆盖的文件数（用于 UI 进度条总量）。排除规则与 safeCopy 一致。
     *
     * @param array<int, string> $excludeRel
     */
    private static function countSourceFiles(string $src, array $excludeRel, string $relBase = ""): int
    {
        $count = 0;
        $dir = @opendir($src);
        if ($dir === false) {
            return 0;
        }
        try {
            while (($name = readdir($dir)) !== false) {
                if ($name === '.' || $name === '..') continue;
                $rel = $relBase === '' ? $name : $relBase . '/' . $name;
                if (self::isExcluded($rel, $excludeRel)) continue;
                $path = $src . DIRECTORY_SEPARATOR . $name;
                if (is_dir($path)) {
                    $count += self::countSourceFiles($path, $excludeRel, $rel);
                } else {
                    $count++;
                }
            }
        } finally {
            closedir($dir);
        }
        return $count;
    }

    /**
     * 从 config/app.php 提取 'version' 字段。使用正则解析避免 include 外部代码副作用。
     */
    private static function detectVersionFromConfig(string $configPath): string
    {
        if (!is_file($configPath)) {
            return '';
        }
        $content = (string)file_get_contents($configPath);
        if ($content === '') {
            return '';
        }
        if (preg_match('/[\'"]version[\'"]\s*=>\s*[\'"]([^\'"]+)[\'"]/', $content, $m)) {
            return trim($m[1]);
        }
        return '';
    }

    /**
     * @param array<int, string> $excludeRel
     */
    private static function isExcluded(string $rel, array $excludeRel): bool
    {
        $rel = str_replace('\\', '/', $rel);
        foreach ($excludeRel as $ex) {
            $ex = str_replace('\\', '/', $ex);
            if ($rel === $ex || str_starts_with($rel, $ex . '/')) {
                return true;
            }
        }
        return false;
    }

    /**
     * @throws JSONException
     */
    public function install(): void
    {
        // 已脱离异次元应用商店：不再上报"新站点已安装"事件
    }

    /**
     * @param string $type
     * @return array
     * @throws JSONException
     */
    public function captcha(string $type): array
    {
        throw new JSONException("应用商店账号体系已停用");
    }

    /**
     * @throws JSONException
     */
    public function register(string $username, string $password, string $captcha, array $cookie): array
    {
        throw new JSONException("应用商店账号体系已停用");
    }

    /**
     * @throws JSONException
     */
    public function login(string $username, string $password): array
    {
        throw new JSONException("应用商店账号体系已停用");
    }

    /**
     * 旧接口：Controller 的 plugins() 已直接走 GithubPluginRegistry，本方法不再被调用。
     * 保留签名返回空，避免任何漏网调用炸到。
     */
    public function plugins(array $data): array
    {
        return ["rows" => [], "count" => 0, "user" => ["id" => 0, "username" => "", "level" => 0], "purchase" => []];
    }

    public function developerPlugins(array $data): array
    {
        return ["rows" => [], "count" => 0, "user" => ["id" => 0, "username" => "", "level" => 0]];
    }

    public function developerCreatePlugin(array $data): array
    {
        throw new JSONException("已脱离应用商店，请通过 GitHub 仓库提交 PR 来发布插件");
    }

    /**
     * @throws JSONException
     */
    public function purchase(int $type, int $pluginId, int $payType): array
    {
        throw new JSONException("插件全部免费，无需购买");
    }

    public function developerCreateKit(array $data): array
    {
        throw new JSONException("已脱离应用商店，请通过 GitHub 仓库提交 PR");
    }

    public function developerDeletePlugin(array $data): array
    {
        throw new JSONException("已脱离应用商店");
    }

    /**
     * 上传文件（原走异次元商店）已停用，返回空 path 以兼容调用方。
     */
    public function upload(array $data): array
    {
        throw new JSONException("应用商店上传通道已停用，请改用本地 zip 安装");
    }

    public function developerUpdatePlugin(array $data): array
    {
        throw new JSONException("已脱离应用商店，请通过 GitHub 仓库提交 PR");
    }

    public function developerPluginPriceSet(array $data): array
    {
        throw new JSONException("已脱离应用商店，插件全部免费");
    }

    /**
     * @throws JSONException
     */
    public function bindLevel(int $authId): array
    {
        throw new JSONException("应用商店账号体系已停用");
    }

    public function levels(): array
    {
        return [];
    }

    public function service(): array
    {
        // 旧前端会读 service() 拿"商店用户信息"渲染头像/等级；返回空让其优雅退化
        return ["id" => 0, "username" => "", "level" => 0, "developer" => 0, "balance" => 0];
    }

    public function editPassword(array $data): array
    {
        throw new JSONException("应用商店账号体系已停用");
    }
}