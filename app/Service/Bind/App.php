<?php
declare(strict_types=1);

namespace App\Service\Bind;

use App\Util\File;
use App\Util\Migrator;
use App\Util\Opcache;
use App\Util\Permission;
use App\Util\Str;
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
     */
    private static function safeCopy(string $src, string $dst, array $excludeRel, string $relBase = ""): void
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
                    self::safeCopy($srcPath, $dstPath, $excludeRel, $rel);
                } else {
                    if (!is_dir(dirname($dstPath))) {
                        @mkdir(dirname($dstPath), 0755, true);
                    }
                    if (!@copy($srcPath, $dstPath)) {
                        throw new \RuntimeException("无法写入文件：{$dstPath}");
                    }
                    @chmod($dstPath, 0644);
                    Opcache::invalidate($dstPath);
                }
            }
        } finally {
            closedir($dir);
        }
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