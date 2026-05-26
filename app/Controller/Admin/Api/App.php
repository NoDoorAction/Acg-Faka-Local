<?php
declare(strict_types=1);

namespace App\Controller\Admin\Api;

use App\Interceptor\ManageSession;
use App\Interceptor\Waf;
use App\Model\ManageLog;
use App\Util\Github;
use App\Util\GithubPluginDownloader;
use App\Util\GithubPluginRegistry;
use App\Util\Http;
use App\Util\Opcache;
use App\Util\PayConfig;
use App\Util\SchemaDiff;
use App\Util\Theme;
use Kernel\Annotation\Inject;
use Kernel\Annotation\Interceptor;
use Kernel\Exception\JSONException;

#[Interceptor([Waf::class, ManageSession::class], Interceptor::TYPE_API)]
class App extends Manage
{
    #[Inject]
    private \App\Service\App $app;

    /**
     * @return array
     */
    public function versions(): array
    {
        return $this->json(200, "ok", $this->app->getVersions());
    }

    /**
     * @return array
     */
    public function latest(): array
    {
        $versions = $this->app->getVersions();
        $latestVersion = $versions[0]['version'];
        $local = config("app")['version'];
        $latest = $latestVersion == $local;
        return $this->json(200, 'ok', ["local" => $local, "latest" => $latest, "version" => $latestVersion]);
    }

    /**
     * @return array
     */
    public function update(): array
    {
        $this->app->update();
        return $this->json(200, "升级完成");
    }

    /**
     * GitHub Releases 列表 —— 字段与原 versions() 同构，前端 timeline 复用。
     * @return array
     * @throws JSONException
     */
    public function githubReleases(): array
    {
        $releases = Github::listReleases();
        $rows = [];
        foreach ($releases as $r) {
            $rows[] = [
                "version" => $r['version'],
                "tag" => $r['tag'],
                "content" => $r['body'] !== '' ? nl2br(htmlspecialchars((string)$r['body'])) : '<i>该版本无发布说明</i>',
                "update_url" => $r['html_url'],
                "update_date" => $r['published_at'] !== '' ? substr((string)$r['published_at'], 0, 10) : '',
                "beta" => $r['prerelease'] ? 1 : 0,
                "overlay" => Github::hasOverlayAsset($r) ? 1 : 0,
            ];
        }
        return $this->json(200, "ok", $rows);
    }

    /**
     * 当前 vs GitHub 最新版本。
     * @return array
     * @throws JSONException
     */
    public function githubLatest(): array
    {
        $latest = Github::latestRelease();
        $local = (string)((array)config("app"))['version'];
        if ($latest === null) {
            return $this->json(200, "ok", ["local" => $local, "latest" => true, "version" => $local, "html_url" => "", "body" => "", "overlay" => 0]);
        }
        $version = (string)$latest['version'];
        return $this->json(200, "ok", [
            "local" => $local,
            "latest" => $version === $local,
            "version" => $version,
            "tag" => (string)$latest['tag'],
            "html_url" => (string)$latest['html_url'],
            "body" => (string)$latest['body'],
            "published_at" => (string)$latest['published_at'],
            "overlay" => Github::hasOverlayAsset($latest) ? 1 : 0,
        ]);
    }

    /**
     * 触发：从 GitHub 下载指定 tag 的源码 zip 并升级。
     * @return array
     * @throws JSONException
     */
    public function githubUpdate(): array
    {
        $tag = trim((string)($_POST['tag'] ?? ''));
        if ($tag === '') {
            throw new JSONException("缺少版本号");
        }

        // 找到对应 release
        $target = null;
        foreach (Github::listReleases() as $r) {
            if ((string)$r['tag'] === $tag || (string)$r['version'] === $tag) {
                $target = $r;
                break;
            }
        }
        if ($target === null) {
            // 兜底：用 latest 是否匹配
            $latest = Github::latestRelease();
            if ($latest !== null && ((string)$latest['tag'] === $tag || (string)$latest['version'] === $tag)) {
                $target = $latest;
            }
        }
        if ($target === null) {
            throw new JSONException("未在 GitHub 仓库中找到版本 {$tag}");
        }

        $url = Github::pickDownloadUrl($target);
        if ($url === '') {
            throw new JSONException("该版本未提供可下载的 zip 资源");
        }

        $dir = BASE_PATH . "/kernel/Install/Update";
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }
        $zipPath = $dir . "/github-" . preg_replace('/[^A-Za-z0-9._\-]/', '_', $tag) . "-" . time() . ".zip";

        if (!Http::download($url, $zipPath)) {
            throw new JSONException("源码 zip 下载失败：{$url}");
        }

        $this->app->updateFromZip($zipPath, Github::normalizeVersion($tag));
        ManageLog::log($this->getManage(), "从 GitHub 升级到 {$tag}");
        return $this->json(200, "升级完成");
    }

    /**
     * 触发：管理员手动上传的整包 zip。
     * @return array
     * @throws JSONException
     */
    /**
     * 数据库结构健康检查：对比 Install.sql 与当前 DB，列出缺失的表/列与建议 SQL。
     * @return array
     * @throws JSONException
     */
    public function schemaCheck(): array
    {
        return $this->json(200, "ok", SchemaDiff::diff());
    }

    public function localUpdate(): array
    {
        $path = (string)($_POST['path'] ?? '');
        $version = trim((string)($_POST['version'] ?? ''));
        if ($path === '' || str_contains($path, '..')) {
            throw new JSONException("非法的安装包路径");
        }
        $src = BASE_PATH . $path;
        if (!is_file($src)) {
            throw new JSONException("升级包不存在，请重新上传");
        }
        if (strtolower((string)pathinfo($src, PATHINFO_EXTENSION)) !== 'zip') {
            @unlink($src);
            throw new JSONException("仅支持 zip 升级包");
        }

        // 版本号留空 → 服务层自动从 zip 内 config/app.php 识别
        $this->app->updateFromZip($src, $version === '' ? '' : Github::normalizeVersion($version));
        ManageLog::log($this->getManage(), $version === '' ? "通过本地 zip 自动识别版本升级" : "通过本地 zip 升级到 {$version}");
        return $this->json(200, "升级完成");
    }

    /**
     * 后台公告接口已停用，永远返回空列表以兼容旧前端。
     * @return array
     */
    public function ad(): array
    {
        return $this->json(200, "ok", []);
    }


    /**
     * 应用商店账号 init 已停用。
     * @return array
     */
    public function init(): array
    {
        return $this->json(200, "ok");
    }

    /**
     * @return array
     */
    public function captcha(): array
    {
        $type = (string)$_GET['type'];
        $captcha = $this->app->captcha($type);
        return $this->json(200, "ok", $captcha);
    }

    /**
     * @throws JSONException
     */
    public function register(): array
    {
        if (!$_POST['username'] || !$_POST['password'] || !$_POST['captcha'] || !$_POST['cookie']) {
            throw new JSONException("所有选项都不能为空");
        }
        $register = $this->app->register((string)$_POST['username'], (string)$_POST['password'], (string)$_POST['captcha'], (array)$_POST['cookie']);
        setConfig([
            "app_id" => $register["id"],
            "app_key" => $register["key"],
        ], BASE_PATH . "/config/store.php");
        Opcache::invalidate(BASE_PATH . "/config/store.php");
        return $this->json(200, "success");
    }

    /**
     * @throws JSONException
     */
    public function login(): array
    {
        if (!$_POST['username'] || !$_POST['password']) {
            throw new JSONException("所有选项都不能为空");
        }
        $login = $this->app->login($_POST['username'], $_POST['password']);
        setConfig([
            "app_id" => $login["id"],
            "app_key" => $login["key"],
        ], BASE_PATH . "/config/store.php");
        Opcache::invalidate(BASE_PATH . "/config/store.php");
        return $this->json(200, "success");
    }

    /**
     * 应用商店插件列表（GitHub 数据源）。返回结构与原异次元商店接口同构，
     * 前端 home.js 表格直接复用，无需改字段。
     */
    public function plugins(): array
    {
        $type = isset($_POST['group']) ? (int)$_POST['group'] : -1;
        $keywords = trim((string)($_POST['keywords'] ?? ''));
        if ($keywords !== '') {
            $keywords = urldecode($keywords);
        }
        $page = max(1, (int)($_POST['page'] ?? 1));
        $limit = max(1, (int)($_POST['limit'] ?? 50));

        $registry = GithubPluginRegistry::fetch();
        $rows = [];
        $index = 0;
        foreach ($registry['items'] as $item) {
            if (!is_array($item)) continue;
            $itemType = (int)($item['type'] ?? 0);
            if ($type !== -1 && $itemType !== $type) continue;

            $name = (string)($item['name'] ?? '');
            $desc = (string)($item['description'] ?? '');
            if ($keywords !== '' && !str_contains($name, $keywords) && !str_contains($desc, $keywords)) {
                continue;
            }

            $row = self::enrichPluginRow($item, $index++);
            $rows[] = $row;
        }

        $total = count($rows);
        $rows = array_slice($rows, ($page - 1) * $limit, $limit);

        $json = $this->json(data: ["list" => $rows, "total" => $total]);
        // 兼容旧前端字段：user / purchase 留空即可
        $json['user'] = ["id" => 0, "username" => "", "level" => 0];
        $json['purchase'] = [];
        return $json;
    }

    /**
     * 把 plugins.json item 转换成前端表格需要的 row 结构（含 install / local_version / icon 绝对 URL）。
     *
     * @param array<string, mixed> $item
     * @return array<string, mixed>
     */
    private static function enrichPluginRow(array $item, int $idx): array
    {
        $type = (int)($item['type'] ?? 0);
        $key = (string)($item['key'] ?? '');
        $row = [
            "id" => $idx + 1,
            "plugin_key" => $key,
            "plugin_name" => (string)($item['name'] ?? $key),
            "version" => (string)($item['version'] ?? '1.0.0'),
            "update_content" => (string)($item['description'] ?? ''),
            "type" => $type,
            "author" => (string)($item['author'] ?? ''),
            "description" => (string)($item['description'] ?? ''),
            "homepage" => (string)($item['homepage'] ?? ''),
            "price" => 0,
            "free" => 1,
            "icon" => "",
        ];

        // icon: 仓库内相对路径转为 raw.githubusercontent.com 的绝对 URL
        $iconRel = (string)($item['icon'] ?? '');
        if ($iconRel !== '') {
            try {
                $r = GithubPluginRegistry::repo();
                $row['icon'] = "https://raw.githubusercontent.com/{$r['owner']}/{$r['repo']}/{$r['branch']}/" . ltrim($iconRel, '/');
            } catch (\Throwable $e) {
                // 配置缺失时忽略 icon
            }
        }

        // 本地是否已安装、本地版本
        $installPath = match ($type) {
            1 => BASE_PATH . "/app/Pay/{$key}",
            2 => BASE_PATH . "/app/View/User/Theme/{$key}",
            default => BASE_PATH . "/app/Plugin/{$key}",
        };
        $entryFile = $type === 2 ? "{$installPath}/Config.php" : "{$installPath}/Config/Info.php";

        if (is_file($entryFile)) {
            $row['install'] = 1;
            try {
                if ($type === 2) {
                    $namespace = "App\\View\\User\\Theme\\{$key}\\Config";
                    if (class_exists($namespace)) {
                        $row['local_version'] = $namespace::INFO["VERSION"] ?? '';
                    }
                } else {
                    $config = require($entryFile);
                    $row['local_version'] = $type === 1
                        ? ($config['version'] ?? '')
                        : ($config[\App\Consts\Plugin::VERSION] ?? '');
                }
            } catch (\Throwable $e) {
                $row['local_version'] = '';
            }
        } else {
            $row['install'] = 0;
            $row['local_version'] = '';
        }

        return $row;
    }

    /**
     * 主动清掉插件 registry 缓存，下次拉列表会重新打 plugins.json。
     */
    public function refreshPluginIndex(): array
    {
        GithubPluginRegistry::clearCache();
        return $this->json(200, "已刷新");
    }

    /**
     * @return array
     */
    /**
     * 检查本地已装插件 vs GitHub 仓库版本，统计可升级数量。
     * 用 plugins.json 作为对照数据源，避免重新拉一次详细列表。
     */
    public function getUpdates(): array
    {
        $file = BASE_PATH . "/runtime/plugin/store.cache";
        $update = BASE_PATH . "/runtime/plugin/update.cache";

        if (is_file($file) && is_file($update) && (filectime($file) + 120) > time()) {
            $updateData = (array)json_decode((string)file_get_contents($update), true) ?: [];
            $appStore = (array)json_decode((string)file_get_contents($file), true) ?: [];
            return array_merge($this->json(200, "ok", $appStore), $updateData);
        }

        try {
            $registry = GithubPluginRegistry::fetch();
        } catch (\Throwable $e) {
            return array_merge($this->json(200, "ok", []), ['generalPlugin' => 0, 'payPlugin' => 0, 'themePlugin' => 0]);
        }

        $appStore = [];
        $generalPlugin = 0;
        $themePlugin = 0;
        $payPlugin = 0;

        foreach ($registry['items'] as $item) {
            if (!is_array($item)) continue;
            $key = (string)($item['key'] ?? '');
            $type = (int)($item['type'] ?? 0);
            $remoteVersion = (string)($item['version'] ?? '');

            $appStore[$key] = [
                "icon" => $item['icon'] ?? '',
                "name" => $item['name'] ?? $key,
                "version" => $remoteVersion,
                "update_content" => $item['description'] ?? '',
                "id" => 0,
                "type" => $type,
            ];

            switch ($type) {
                case 0:
                    $plg = \Kernel\Util\Plugin::getPlugin($key);
                    if (!empty($plg) && ($plg['VERSION'] ?? '') !== $remoteVersion) {
                        $generalPlugin++;
                    }
                    break;
                case 1:
                    $plg = PayConfig::info($key);
                    if (!empty($plg) && ($plg['version'] ?? '') !== $remoteVersion) {
                        $payPlugin++;
                    }
                    break;
                case 2:
                    $plg = Theme::getConfig($key);
                    if (!empty($plg) && (($plg['info']['VERSION'] ?? '') !== $remoteVersion)) {
                        $themePlugin++;
                    }
                    break;
            }
        }

        $updateData = ['generalPlugin' => $generalPlugin, 'payPlugin' => $payPlugin, 'themePlugin' => $themePlugin];
        @mkdir(dirname($file), 0777, true);
        file_put_contents($file, json_encode($appStore));
        file_put_contents($update, json_encode($updateData));
        return array_merge($this->json(200, "ok", $appStore), $updateData);
    }

    /**
     * @return array
     */
    public function delUpdates(): array
    {
        $file = BASE_PATH . "/runtime/plugin/store.cache";
        unlink($file);
        return $this->json(200, "ok");
    }

    /**
     * @return array
     */
    public function purchase(): array
    {
        $purchase = $this->app->purchase((int)$_POST['type'], (int)$_POST['plugin_id'], (int)$_POST['payType']);
        return $this->json(200, "下单成功", $purchase);
    }

    /**
     * @return array
     */
    public function install(): array
    {
        $this->app->installPlugin((string)$_POST['plugin_key'], (int)$_POST['type'], (int)$_POST['plugin_id']);
        ManageLog::log($this->getManage(), "安装了应用({$_POST['plugin_key']})");
        return $this->json(200, "安装完成");
    }

    /**
     * 本地 zip 安装：跳过应用商店，直接用本地安装包解压到对应目录。
     * @return array
     * @throws JSONException
     */
    public function localInstall(): array
    {
        $type = (int)$_POST['type'];
        $pluginKey = trim((string)$_POST['plugin_key']);
        $path = (string)$_POST['path'];

        if (!preg_match('/^[A-Za-z][A-Za-z0-9_]*$/', $pluginKey)) {
            throw new JSONException("插件标识仅支持字母/数字/下划线，且需以字母开头");
        }
        if (!in_array($type, [0, 1, 2], true)) {
            throw new JSONException("插件类型错误");
        }
        if ($path === '' || str_contains($path, '..')) {
            throw new JSONException("非法的安装包路径");
        }

        $pluginPath = match ($type) {
            1 => BASE_PATH . "/app/Pay/{$pluginKey}/",
            2 => BASE_PATH . "/app/View/User/Theme/{$pluginKey}/",
            default => BASE_PATH . "/app/Plugin/{$pluginKey}/",
        };
        $entryFile = $type === 2 ? $pluginPath . "Config.php" : $pluginPath . "Config/Info.php";

        if (is_file($entryFile)) {
            throw new JSONException("该插件标识已存在，请先卸载或更换标识");
        }

        $src = BASE_PATH . $path;
        if (!is_file($src)) {
            throw new JSONException("安装包不存在，请重新上传");
        }
        if (strtolower((string)pathinfo($src, PATHINFO_EXTENSION)) !== 'zip') {
            @unlink($src);
            throw new JSONException("仅支持 zip 安装包");
        }

        if (!is_dir($pluginPath) && !mkdir($pluginPath, 0777, true)) {
            throw new JSONException("无法创建插件目录，请检查写入权限");
        }

        if (!\App\Util\Zip::unzip($src, $pluginPath)) {
            \App\Util\File::delDirectory($pluginPath);
            @unlink($src);
            throw new JSONException("解压失败，请检查安装包是否完整或目录是否可写");
        }

        // 清理上传缓存
        @unlink($src);

        if (!is_file($entryFile)) {
            \App\Util\File::delDirectory($pluginPath);
            throw new JSONException("安装包格式不正确，未找到入口配置文件，请确认插件类型选择是否正确");
        }

        // 通用插件 / 支付插件：导入 install.sql（与远程安装行为对齐）
        if ($type !== 2) {
            $installSql = $pluginPath . "install.sql";
            if (is_file($installSql)) {
                $database = config("database");
                \Kernel\Util\SQL::import(
                    $installSql,
                    $database['host'],
                    $database['database'],
                    $database['username'],
                    $database['password'],
                    $database['prefix']
                );
            }
        }

        // 通用插件：触发 install 钩子
        if ($type === 0) {
            \Kernel\Util\Plugin::runHookState($pluginKey, \Kernel\Annotation\Plugin::INSTALL);
        }

        ManageLog::log($this->getManage(), "本地安装了应用({$pluginKey})");
        return $this->json(200, "本地安装完成");
    }

    /**
     * @return array
     */
    public function upgrade(): array
    {
        $this->app->updatePlugin((string)$_POST['plugin_key'], (int)$_POST['type'], (int)$_POST['plugin_id']);
        ManageLog::log($this->getManage(), "更新了应用({$_POST['plugin_key']})");
        return $this->json(200, "更新完成");
    }

    /**
     * @return array
     */
    public function uninstall(): array
    {
        //卸载插件
        $pluginKey = (string)$_POST['plugin_key'];
        $type = (int)$_POST['type'];

        if ($type == 0) {
            _plugin_stop($pluginKey);
        }

        $this->app->uninstallPlugin($pluginKey, $type);

        ManageLog::log($this->getManage(), "卸载了应用({$pluginKey})");
        return $this->json(200, "卸载完成");
    }

    /**
     * 开发者插件
     * @return array
     */
    public function developerPlugins(): array
    {
        $plugins = $this->app->developerPlugins([
            "page" => (int)$_POST['page'],
            "limit" => (int)$_POST['limit']
        ]);

        foreach ($plugins['rows'] as &$plugin) {
            $plugin['icon'] = \App\Service\App::APP_URL . "/{$plugin['icon']}";
        }

        $json = $this->json(data: [
            "list" => $plugins['rows'],
            "total" => $plugins['count']
        ]);
        $json['user'] = $plugins['user'];
        return $json;
    }


    /**
     * 创建插件
     * @return array
     * @throws JSONException
     */
    public function developerCreatePlugin(): array
    {
        $file = $_POST['icon'];
        if (!file_exists(BASE_PATH . $file)) {
            throw new JSONException("请上传图标");
        }
        $iconBody = file_get_contents(BASE_PATH . $file);
        $_POST['icon'] = $iconBody;
        return $this->json(200, "创建成功", $this->app->developerCreatePlugin($_POST));
    }

    /**
     * @throws JSONException
     */
    public function developerCreateKit(): array
    {
        $file = $_POST['resource'];
        if (!file_exists(BASE_PATH . $file)) {
            throw new JSONException("请重新上传插件包");
        }
        //上传安装包
        $upload = $this->app->upload([
            [
                'name' => 'file',
                'contents' => fopen(BASE_PATH . $file, 'r'),
                'filename' => 'file.zip'
            ]
        ]);
        //删除本地安装包
        unlink(BASE_PATH . $file);
        //需要审核的安装包临时存放地址
        $_POST['resource'] = $upload['path'];
        return $this->json(200, "提交成功", $this->app->developerCreateKit($_POST));
    }

    /**
     * @return array
     */
    public function developerDeletePlugin(): array
    {
        return $this->json(200, "删除成功", $this->app->developerDeletePlugin($_POST));
    }

    /**
     * @return array
     * @throws JSONException
     */
    public function developerUpdatePlugin(): array
    {
        $file = $_POST['audit_resource'];
        if (!file_exists(BASE_PATH . $file)) {
            throw new JSONException("请重新上传插件包");
        }
        //上传更新包
        $upload = $this->app->upload([
            [
                'name' => 'file',
                'contents' => fopen(BASE_PATH . $file, 'r'),
                'filename' => 'file.zip'
            ]
        ]);
        //删除本地更新包
        unlink(BASE_PATH . $file);
        //需要审核的安装包临时存放地址
        $_POST['audit_resource'] = $upload['path'];
        return $this->json(200, "提交成功", $this->app->developerUpdatePlugin($_POST));
    }

    /**
     * @return array
     */
    public function developerPluginPriceSet(): array
    {
        return $this->json(200, "新的定价已生效", $this->app->developerPluginPriceSet($_POST));
    }


    /**
     * @return array
     */
    public function purchaseRecords(): array
    {
        return $this->json(data: ["list" => $this->app->purchaseRecords((int)$_GET['plugin_id'])]);
    }

    /**
     * @return array
     */
    public function unbind(): array
    {
        $this->app->unbind((int)$_POST['auth_id']);
        return $this->json(200, "绑定授权成功");
    }

    /**
     * @throws JSONException
     */
    /**
     * 商店线路切换接口已停用，本地化版本不再使用任何远程商店服务器。
     * 保留方法签名兼容旧的前端 JS，永远返回成功不做任何事。
     * @return array
     */
    public function setServer(): array
    {
        return $this->json(200, "ok");
    }

    /**
     * @return array
     */
    public function levels(): array
    {
        return $this->json(data: ["list" => $this->app->levels()]);
    }

    /**
     * @return array
     */
    public function bindLevel(): array
    {
        $this->app->bindLevel((int)$_POST['auth_id']);
        return $this->json(200, "绑定授权成功");
    }

    /**
     * @return array
     */
    public function service(): array
    {
        return $this->json(data: $this->app->service());
    }


    /**
     * @return array
     */
    public function editPassword(): array
    {
        $this->app->editPassword($_POST);
        return $this->json();
    }
}