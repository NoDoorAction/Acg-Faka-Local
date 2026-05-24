<?php
declare (strict_types=1);

namespace Kernel\Plugin;

use App\Util\Client;
use Kernel\Component\Singleton;
use Kernel\Consts\Base;
use Kernel\Util\Binary;
use Kernel\Util\Context;
use Kernel\Util\File;
use Kernel\Util\Plugin;

class Hook
{

    use Singleton;

    public const CACHE_FILE = BASE_PATH . "/runtime/plugin/hook";


    /**
     * @return void
     */
    public function load(): void
    {
        $path = BASE_PATH . "/runtime/plugin/";
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }

        if (is_file(Hook::CACHE_FILE) && !is_writable(Hook::CACHE_FILE)) {
            return;
        }

        $hooks = is_file(Hook::CACHE_FILE)
            ? File::read(Hook::CACHE_FILE, function (string $contents) {
                return Binary::inst()->unpack($contents, _plugin_get_hwid());
            })
            : null;

        // 缓存不存在或解密失败（如来自旧服务器的指纹）→ 自动重建
        if (!is_array($hooks) || $hooks === []) {
            $hooks = $this->rebuild();
        }

        foreach ($hooks as $points) {
            foreach ($points as $a => $point) {
                foreach ($point as $plugin) {
                    Plugin::$container['hook'][$a][] = ["namespace" => $plugin['namespace'], "method" => $plugin['method'], "pluginName" => $plugin['pluginName']];
                }
            }
        }

        $route = explode("/", trim(Context::get(Base::ROUTE), "/"));
        if (strtolower($route[0]) == "plugin") {
            $pluginName = ucfirst($route[1]);
            $pluginCfg = Plugin::getPlugin($pluginName);
            if ($pluginCfg['PLUGIN_CONFIG']['STATUS'] != 1) {
                Client::redirect("/", "当前插件未启用");
            }
        }
    }

    /**
     * 扫描所有已启用插件，重新构建 hook 缓存。
     * 用于：解密失败（指纹不匹配）或缓存丢失时的自恢复。
     * @return array
     */
    private function rebuild(): array
    {
        $pluginRoot = BASE_PATH . "/app/Plugin/";
        if (!is_dir($pluginRoot)) {
            return [];
        }

        // 先清空缓存文件，让 _plugin_hook_add 从空状态开始
        if (is_file(Hook::CACHE_FILE)) {
            @unlink(Hook::CACHE_FILE);
        }

        foreach (File::scan($pluginRoot) as $name) {
            $cfgFile = $pluginRoot . $name . "/Config/Config.php";
            $infoFile = $pluginRoot . $name . "/Config/Info.php";
            if (!is_file($infoFile) || !is_file($cfgFile)) {
                continue;
            }
            $cfg = (array)require $cfgFile;
            if ((int)($cfg['STATUS'] ?? 0) !== 1) {
                continue;
            }
            _plugin_hook_add($name);
        }

        if (!is_file(Hook::CACHE_FILE)) {
            return [];
        }

        $hooks = Binary::inst()->unpack(
            (string)file_get_contents(Hook::CACHE_FILE),
            _plugin_get_hwid()
        );
        return is_array($hooks) ? $hooks : [];
    }

    /**
     * @param string $name
     * @return void
     */
    public function del(string $name): void
    {
        _plugin_hook_del($name);
    }


    /**
     * @param string $name
     * @return void
     */
    public function add(string $name): void
    {
        _plugin_hook_add($name);
    }


    /**
     * @param string $name
     * @param int $point
     * @param string $namespace
     * @param string $method
     * @return bool
     */
    public function exist(string $name, int $point, string $namespace, string $method): bool
    {
        return _plugin_hook_exist($name, $point, $namespace, $method);
    }
}