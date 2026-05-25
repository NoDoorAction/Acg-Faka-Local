<?php
declare(strict_types=1);

namespace App\Service\Bind;

use App\Util\File;
use App\Util\Http;
use App\Util\Migrator;
use App\Util\Opcache;
use App\Util\Str;
use App\Util\Zip;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use Kernel\Annotation\Inject;
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

    #[Inject]
    private Client $client;

    /**
     * @param string $uri
     * @param array $data
     * @param array|null $cookies
     * @return mixed
     * @throws JSONException
     */
    private function post(string $uri, array $data = [], ?array &$cookies = null): mixed
    {
        try {
            $form = [
                "form_params" => $data,
                "verify" => false
            ];
            if (is_array($cookies)) {
                $form["cookies"] = CookieJar::fromArray([
                    "GOLANG_ID" => $cookies['GOLANG_ID']
                ], parse_url(self::APP_URL)['host']);
            }
            $response = $this->client->post(self::APP_URL . $uri, $form);
            if ($cookies !== null) {
                $cookie = implode(";", (array)$response->getHeader("Set-Cookie"));
                $explode = explode(";", $cookie);
                $cookies = [];
                foreach ($explode as $item) {
                    $it = explode("=", $item);
                    $cookies[trim((string)$it[0])] = trim((string)$it[1]);
                }
            }
            $res = (array)json_decode((string)$response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            throw new JSONException("应用商店请求错误");
        }
        if ($res['code'] != 200) {
            throw new JSONException($res['msg']);
        }

        return $res['data'];
    }

    /**
     * @param string $uri
     * @param array $data
     * @return mixed
     * @throws GuzzleException
     * @throws JSONException
     */
    private function storeRequest(string $uri, array $data = []): mixed
    {
        $store = config("store");
        $data['sign'] = Str::generateSignature($data, (string)$store["app_key"]);
        $response = $this->client->post(self::APP_URL . $uri, [
            "form_params" => $data,
            "headers" => ["appId" => (int)$store['app_id'], "appKey" => _plugin_get_hwid()],
            "verify" => false
        ]);
        $res = (array)json_decode((string)$response->getBody()->getContents(), true);

        if ($res['code'] != 200) {
            throw new JSONException($res['msg']);
        }
        return $res['data'];
    }

    /**
     * @param string $uri
     * @param array $data
     * @return array|null
     * @throws GuzzleException
     */
    private function storeDownload(string $uri, array $data = []): ?string
    {
        $store = config("store");
        $data['sign'] = Str::generateSignature($data, (string)$store["app_key"]);

        $path = BASE_PATH . "/kernel/Install/OS/";
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }
        $fileName = md5((string)time()) . ".zip";
        $fileHandle = fopen($path . $fileName, "w+");
        $response = $this->client->post(self::APP_URL . $uri, [
            "form_params" => $data,
            "verify" => false,
            "headers" => ["appId" => (int)$store['app_id'], "appKey" => _plugin_get_hwid()],
            RequestOptions::SINK => $fileHandle
        ]);

        if ($response->getStatusCode() === 200) {
            return $fileName;
        }

        return null;
    }

    /**
     * @return array
     * @throws JSONException
     */
    public function getVersions(): array
    {
        if (Context::get(Base::LOCK) == "") {
            file_put_contents(BASE_PATH . "/kernel/Install/Lock", Str::generateRandStr(32));
        }

        // 隐私保护：心跳不上报真实站点域名 / 出口 IP
        return (array)$this->post("/open/project/version", [
            "key" => "faka",
            "domain" => "0.0.0.0",
            "client_ip" => "0.0.0.0",
        ]);
    }

    /**
     * @param string $key
     * @param int $type 插件类型
     * @param int $pluginId
     * @throws GuzzleException
     * @throws JSONException
     * @throws \ReflectionException
     */
    public function installPlugin(string $key, int $type, int $pluginId): void
    {
        //默认位置，通用插件
        $pluginPath = BASE_PATH . "/app/Plugin/{$key}/";
        $fileInit = file_exists($pluginPath . "/Config/Info.php");
        if ($type == 1) {
            //支付插件
            $pluginPath = BASE_PATH . "/app/Pay/{$key}/";
            $fileInit = file_exists($pluginPath . "/Config/Info.php");
        } elseif ($type == 2) {
            //网站模板
            $pluginPath = BASE_PATH . "/app/View/User/Theme/{$key}/";
            $fileInit = file_exists($pluginPath . "/Config.php");
        }

        if (!is_dir($pluginPath)) {
            mkdir($pluginPath, 0777, true);
        }

        if ($fileInit) {
            throw new JSONException("该插件已被安装，请勿重复安装");
        }

        $storeDownload = $this->storeDownload("/store/install", [
            "plugin_id" => $pluginId
        ]);
        if (!$storeDownload) {
            throw new JSONException("安装失败，请联系技术人员");
        }
        //下载完成，开始安装
        $src = BASE_PATH . "/kernel/Install/OS/{$storeDownload}";
        if (!Zip::unzip($src, $pluginPath)) {
            throw new JSONException("安装失败，请检查是否有写入权限");
        }
        //安装完成，删除src
        unlink($src);
        //判断目标目录是否有install.sqll
        $installSql = $pluginPath . "install.sql";
        if (file_exists($installSql)) {
            $database = config("database");
            SQL::import($installSql, $database['host'], $database['database'], $database['username'], $database['password'], $database['prefix']);
        }

        if ($type == 0) {
            //安装
            Plugin::runHookState($key, \Kernel\Annotation\Plugin::INSTALL);
        }
    }

    /**
     * @param string $key
     * @param int $type
     * @param int $pluginId
     * @throws GuzzleException
     * @throws JSONException
     * @throws \ReflectionException
     */
    public function updatePlugin(string $key, int $type, int $pluginId): void
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
        if (!is_dir($pluginPath)) {
            throw new JSONException("该插件还未安装，请先安装插件后再进行更新");
        }
        $storeDownload = $this->storeDownload("/store/update", [
            "plugin_id" => $pluginId
        ]);
        if (!$storeDownload) {
            throw new JSONException("更新失败，请联系技术人员");
        }
        //下载完成，开始安装
        $src = BASE_PATH . "/kernel/Install/OS/{$storeDownload}";
        if (!Zip::unzip($src, $pluginPath)) {
            throw new JSONException("更新失败，请检查是否有写入权限");
        }
        //更新完成，删除src
        unlink($src);
        //判断目标目录是否有update.sql
        $updateSql = $pluginPath . "update.sql";
        if (file_exists($updateSql)) {
            $database = config("database");
            SQL::import($updateSql, $database['host'], $database['database'], $database['username'], $database['password'], $database['prefix']);
        }

        if ($type == 0) {
            Plugin::runHookState($key, \Kernel\Annotation\Plugin::UPGRADE);
        } elseif ($type == 2) {
            //清空模版缓存
            $viewDir = realpath(BASE_PATH . "/runtime/view/");
            if ($viewDir) {
                File::delDirectory($viewDir);
            }
        }

        $files = [BASE_PATH . '/runtime/plugin/store.cache', BASE_PATH . '/runtime/plugin/update.cache'];
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
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
     * @throws GuzzleException
     * @throws JSONException
     */
    public function purchaseRecords(int $pluginId): array
    {
        return $this->storeRequest("/store/records", [
            "plugin_id" => $pluginId
        ]);
    }

    /**
     * @throws GuzzleException
     * @throws JSONException
     */
    public function unbind(int $authId): array
    {
        return $this->storeRequest("/store/unbind", [
            "auth_id" => $authId
        ]);
    }

    /**
     * @throws GuzzleException
     * @throws JSONException
     */
    public function update(): void
    {
        $versions = $this->getVersions();
        $latestVersion = $versions[0]['version'];
        $localVersion = config("app")['version'];
        if ($latestVersion == $localVersion) {
            throw new JSONException("你已经是最新版本了");
        }

        $vrs = array_reverse($versions);
        $startVersion = 0;

        foreach ($vrs as $index => $vr) {
            if ($vr['version'] == $localVersion) {
                $startVersion = $index;
                break;
            }
        }

        foreach ($vrs as $key => $val) {
            if ($startVersion < $key) {
                //下载完成，写入到本地缓存目录
                $zipPath = BASE_PATH . '/kernel/Install/Update/' . $val['version'];

                //下载更新包
                if (!Http::download($val['update_url'], $zipPath . '/update.zip')) {
                    throw new JSONException("更新包下载失败");
                }

                if (!Zip::unzip($zipPath . '/update.zip', $zipPath)) {
                    throw new JSONException("ZIP解压缩失败，请检查程序是否有写入权限！");
                }

                //升级数据库
                $sql = $zipPath . '/update.sql';

                if (file_exists($sql)) {
                    //导入数据库
                    $database = config("database");
                    SQL::import($sql, $database['host'], $database['database'], $database['username'], $database['password'], $database['prefix']);
                }

                //升级程序，防止sql等命令错误，通过php代码来执行sql，新增时间：2022/04/07
                $ext = $zipPath . '/update.php';
                if (file_exists($ext)) {
                    require($ext);
                    $class = "\\Version" . str_replace(".", "", $val['version']) . "\\Update";
                    if (!class_exists($class)) {
                        throw new JSONException("更新主类未装载成功，请重试");
                    }
                    $updateObj = new $class();
                    if (!method_exists($updateObj, "handle")) {
                        throw new JSONException("更新子程序不存在，请重试");
                    }
                    $updateObj->handle();
                }

                //升级程序
                try {
                    File::copyDirectory($zipPath . '/file', BASE_PATH);
                } catch (\Exception $e) {
                    throw new JSONException($e->getMessage());
                }

                //升级完成，记录版本号
                setConfig(["version" => $val["version"]], BASE_PATH . "/config/app.php");
            }
        }
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
     * @return array
     * @throws JSONException
     */
    public function ad(): array
    {
        // 隐私保护：不再向应用商店拉取广告
        return [];
    }

    /**
     * @throws JSONException
     */
    public function install(): void
    {
        $this->post("/open/project/install", ["key" => "faka"]);
    }

    /**
     * @param string $type
     * @return array
     * @throws JSONException
     */
    public function captcha(string $type): array
    {
        $cookie = [];
        $result = (array)$this->post("/auth/captcha", [
            "type" => $type
        ], $cookie);
        $result["cookie"] = $cookie;
        return $result;
    }

    /**
     * @param string $username
     * @param string $password
     * @param string $captcha
     * @param array $cookie
     * @return array
     * @throws JSONException
     */
    public function register(string $username, string $password, string $captcha, array $cookie): array
    {
        return (array)$this->post("/auth/register", [
            "captcha" => $captcha,
            "username" => $username,
            "password" => $password
        ], $cookie);
    }

    /**
     * @throws JSONException
     */
    public function login(string $username, string $password): array
    {
        return (array)$this->post("/auth/login", [
            "username" => $username,
            "password" => $password
        ]);
    }

    /**
     * @throws GuzzleException
     * @throws JSONException
     */
    public function plugins(array $data): array
    {
        return $this->storeRequest("/store/plugins", $data);
    }

    /**
     * @param array $data
     * @return array
     * @throws GuzzleException
     * @throws JSONException
     */
    public function developerPlugins(array $data): array
    {
        return $this->storeRequest("/developer/plugins", $data);
    }


    /**
     * @param array $data
     * @return array
     * @throws GuzzleException
     * @throws JSONException
     */
    public function developerCreatePlugin(array $data): array
    {
        return $this->storeRequest("/developer/create", $data);
    }

    /**
     * @throws GuzzleException
     * @throws JSONException
     */
    public function purchase(int $type, int $pluginId, int $payType): array
    {
        return $this->storeRequest("/store/purchase", [
            "type" => $type,
            "payType" => $payType,
            "plugin_id" => $pluginId,
            "return" => \App\Util\Client::getUrl() . "/admin/store/home"
        ]);
    }

    /**
     * @param array $data
     * @return array
     * @throws GuzzleException
     * @throws JSONException
     */
    public function developerCreateKit(array $data): array
    {
        return $this->storeRequest("/developer/createKit", $data);
    }


    /**
     * @throws GuzzleException
     * @throws JSONException
     */
    public function developerDeletePlugin(array $data): array
    {
        return $this->storeRequest("/developer/deletePlugin", $data);
    }

    /**
     * @param array $data
     * @return array
     * @throws JSONException
     */
    public function upload(array $data): array
    {
        try {
            $form = [
                "multipart" => $data,
                "verify" => false
            ];
            $response = $this->client->post(self::APP_URL . "/open/project/upload", $form);
            $res = (array)json_decode((string)$response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            throw new JSONException("应用商店连接失败");
        }
        if ($res['code'] != 200) {
            throw new JSONException($res['msg']);
        }
        return $res['data'];
    }

    /**
     * @param array $data
     * @return array
     * @throws GuzzleException
     * @throws JSONException
     */
    public function developerUpdatePlugin(array $data): array
    {
        return $this->storeRequest("/developer/createUpdate", $data);
    }

    /**
     * @param array $data
     * @return array
     * @throws GuzzleException
     * @throws JSONException
     */
    public function developerPluginPriceSet(array $data): array
    {
        return $this->storeRequest("/developer/priceSet", $data);
    }

    /**
     * @param int $authId
     * @return array
     * @throws GuzzleException
     * @throws JSONException
     */
    public function bindLevel(int $authId): array
    {
        return $this->storeRequest("/store/bindLevel", ["auth_id" => $authId]);
    }


    /**
     * @return array
     * @throws GuzzleException
     * @throws JSONException
     */
    public function levels(): array
    {
        return $this->storeRequest("/store/levels");
    }

    /**
     * @return array
     * @throws GuzzleException
     * @throws JSONException
     */
    public function service(): array
    {
        return $this->storeRequest("/store/service");
    }


    /**
     * @param array $data
     * @return array
     * @throws GuzzleException
     * @throws JSONException
     */
    public function editPassword(array $data): array
    {
        return $this->storeRequest("/store/editPassword", $data);
    }
}