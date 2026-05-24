<?php
declare(strict_types=1);

use Kernel\Annotation\Hook as HookAnnotation;
use Kernel\Annotation\Plugin as PluginAnnotation;
use Kernel\Consts\Base;
use Kernel\Plugin\Hook;
use Kernel\Util\Binary;
use Kernel\Util\Context;
use Kernel\Util\File;
use Kernel\Util\Plugin as PluginUtil;

$hook_del_name = null;
$hook_generator_name = null;
$hook_generator_path = null;

function _plugin_get_hwid(): string
{
    // 本地化改造：返回固定指纹，移除对数据库/路径的依赖，
    // 让所有已安装插件可在任何服务器自由迁移而不被应用商店校验拒绝。
    return "ACGFAKALOCALHWID";
}

function _plugin_aes_decrypt($data, $key, $iv)
{
    return openssl_decrypt($data, 'aes-128-cbc', $key, OPENSSL_RAW_DATA, $iv);
}

function _plugin_aes_encrypt($data, $key, $iv)
{
    return openssl_encrypt($data, 'aes-128-cbc', $key, OPENSSL_RAW_DATA, $iv);
}

function _plugin_encrypt($data)
{
    return Binary::inst()->pack($data, _plugin_get_hwid());
}

function _plugin_decrypt($data)
{
    return Binary::inst()->unpack($data, _plugin_get_hwid()) ?: [];
}

function _plugin_set_config($data, $file, $reset = false)
{
    setConfig((array)$data, $file, $reset);
}

function _plugin_start($name, $check = false)
{
    $path = BASE_PATH . "/app/Plugin/{$name}";
    $configFile = $path . "/Config/Config.php";

    if (!is_dir($path) || !is_file($path . "/Config/Info.php")) {
        return null;
    }

    \App\Model\Config::get('user_theme');
    \App\Model\Config::get('user_mobile_theme');

    $config = is_file($configFile) ? (array)require $configFile : [];
    $config['STATUS'] = 1;
    _plugin_set_config($config, $configFile, true);

    _plugin_hook_add($name);

    if (!$check) {
        PluginUtil::runHookState($name, PluginAnnotation::START);
    }

    File::remove(BASE_PATH . "/runtime/plugin/plugin.cache");
    return null;
}

function _plugin_stop($name)
{
    $path = BASE_PATH . "/app/Plugin/{$name}";
    $configFile = $path . "/Config/Config.php";
    if (!is_file($configFile)) {
        return null;
    }

    $config = (array)require $configFile;
    $config['STATUS'] = '0';
    _plugin_set_config($config, $configFile, true);

    PluginUtil::runHookState($name, PluginAnnotation::STOP);
    _plugin_hook_del($name);
    File::remove(BASE_PATH . "/runtime/plugin/plugin.cache");
    return null;
}

function _plugin_hook_add_handle($contents)
{
    global $hook_generator_name, $hook_generator_path;

    $hooks = _plugin_decrypt($contents);
    if (!is_array($hooks)) {
        $hooks = [];
    }

    $name = $hook_generator_name;
    $path = $hook_generator_path;

    if ($name && is_dir($path)) {
        // 先清掉该插件的旧 hook，避免重复注册
        unset($hooks[$name]);
        $scanned = $hooks[$name] ?? [];

        foreach (File::scan($path, true) as $file) {
            $className = trim((string)explode('.', $file)[0]);
            $namespace = "\\App\\Plugin\\{$name}\\Hook\\{$className}";
            if (!class_exists($namespace)) {
                continue;
            }
            try {
                $reflectionClass = new \ReflectionClass($namespace);
            } catch (\ReflectionException) {
                continue;
            }
            foreach ($reflectionClass->getMethods() as $method) {
                foreach ($method->getAttributes() as $attribute) {
                    if ($attribute->getName() !== HookAnnotation::class) {
                        continue;
                    }
                    $args = $attribute->getArguments();
                    $point = $args['point'] ?? ($args[0] ?? null);
                    if ($point === null) {
                        continue;
                    }
                    $scanned[(int)$point][] = [
                        'pluginName' => $name,
                        'namespace' => $namespace,
                        'method' => $method->getName(),
                    ];
                }
            }
        }

        if ($scanned) {
            $hooks[$name] = $scanned;
        }
    }

    return _plugin_encrypt($hooks);
}

function _plugin_hook_del_handle($contents)
{
    global $hook_del_name;

    $hooks = _plugin_decrypt($contents);
    if (!is_array($hooks)) {
        $hooks = [];
    }

    $name = $hook_del_name;
    if ($name) {
        unset($hooks[$name]);
    }

    return _plugin_encrypt($hooks);
}

function _plugin_hook_exist_handle($contents)
{
    return _plugin_decrypt($contents);
}

function _plugin_hook_add($name)
{
    global $hook_generator_name, $hook_generator_path;

    $hook_generator_name = $name;
    $hook_generator_path = BASE_PATH . "/app/Plugin/{$name}/Hook/";
    File::writeForLock(Hook::CACHE_FILE, '_plugin_hook_add_handle');
    return null;
}

function _plugin_hook_del($name)
{
    global $hook_del_name;

    $hook_del_name = $name;
    File::writeForLock(Hook::CACHE_FILE, '_plugin_hook_del_handle');
    return null;
}

function _plugin_hook_exist($name, $point, $namespace, $method)
{
    if (!file_exists(Hook::CACHE_FILE)) {
        return false;
    }

    Context::set(Base::LOCK, [$name, $point, $namespace, $method]);
    $hooks = File::read(Hook::CACHE_FILE, '_plugin_hook_exist_handle');

    foreach (($hooks[$name][$point] ?? []) as $item) {
        if (($item['pluginName'] ?? null) === $name
            && ($item['namespace'] ?? null) === $namespace
            && ($item['method'] ?? null) === $method) {
            return true;
        }
    }

    return false;
}
