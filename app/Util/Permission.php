<?php
declare(strict_types=1);

namespace App\Util;

/**
 * 文件系统权限辅助：
 *
 * - ensureWritable(): 在写文件/目录之前尝试自动 chmod 解决 mode 不够的情况
 * - diagnose():       写失败时生成可读的诊断字符串（文件所有者 / 权限 / web 进程用户），
 *                     便于运维一眼定位 owner 错位、open_basedir 等问题
 *
 * 注意：PHP chmod 只对当前进程拥有的文件生效；owner 错位时本类无能为力，
 * 此时应直接给出诊断信息让管理员手动执行 chown / chmod。
 */
class Permission
{
    public const FILE_MODE = 0666;
    public const DIR_MODE = 0777;

    /**
     * 在写入某个文件前，确保父目录和文件本身都可写。
     * 已存在但不可写的会尝试 chmod 一次。
     */
    public static function ensureFileWritable(string $file): void
    {
        $dir = dirname($file);
        self::ensureDirWritable($dir);
        if (is_file($file) && !is_writable($file)) {
            @chmod($file, self::FILE_MODE);
        }
    }

    /**
     * 确保目录存在且可写。不存在则递归创建。已存在但不可写则尝试 chmod。
     */
    public static function ensureDirWritable(string $dir): void
    {
        if (!is_dir($dir)) {
            @mkdir($dir, self::DIR_MODE, true);
            return;
        }
        if (!is_writable($dir)) {
            @chmod($dir, self::DIR_MODE);
        }
    }

    /**
     * 在删除前递归把整棵树 chmod 到可写，避免 read-only 文件导致 rmdir/unlink 失败。
     */
    public static function makeTreeWritable(string $path): void
    {
        if (!file_exists($path)) {
            return;
        }
        if (is_file($path) || is_link($path)) {
            @chmod($path, self::FILE_MODE);
            return;
        }
        @chmod($path, self::DIR_MODE);
        $dh = @opendir($path);
        if ($dh === false) {
            return;
        }
        while (($name = readdir($dh)) !== false) {
            if ($name === '.' || $name === '..') {
                continue;
            }
            self::makeTreeWritable($path . DIRECTORY_SEPARATOR . $name);
        }
        closedir($dh);
    }

    /**
     * 生成诊断信息字符串（owner / group / mode / web 进程用户 / open_basedir 限制等）。
     */
    public static function diagnose(string $path): string
    {
        $lines = [];
        $lines[] = "路径: {$path}";
        if (!file_exists($path)) {
            $parent = dirname($path);
            $lines[] = "状态: 不存在；父目录: {$parent} " . (is_dir($parent) ? "(存在)" : "(也不存在)");
            if (is_dir($parent)) {
                $lines[] = "父目录可写: " . (is_writable($parent) ? "是" : "否");
                $lines[] = self::ownerLine($parent);
            }
        } else {
            $lines[] = "可写: " . (is_writable($path) ? "是" : "否");
            $lines[] = self::ownerLine($path);
        }

        // 当前 PHP 进程用户
        $webUser = function_exists('posix_geteuid') && function_exists('posix_getpwuid')
            ? (posix_getpwuid(posix_geteuid())['name'] ?? (string)posix_geteuid())
            : (string)(get_current_user() ?: '(unknown)');
        $lines[] = "PHP 进程用户: {$webUser}";

        $openBasedir = (string)ini_get('open_basedir');
        if ($openBasedir !== '') {
            $lines[] = "open_basedir: {$openBasedir}";
        }
        if ((string)ini_get('safe_mode') === '1') {
            $lines[] = "safe_mode: on";
        }
        return implode("\n", $lines);
    }

    /**
     * 站点应当可写的目录清单（安装 / 升级后自动 chmod 到这些目录）。
     *
     * @return array<int, string>
     */
    public static function writableDirs(): array
    {
        return [
            BASE_PATH . "/runtime",
            BASE_PATH . "/config",
            BASE_PATH . "/kernel/Install",
            BASE_PATH . "/app/Plugin",
            BASE_PATH . "/app/Pay",
            BASE_PATH . "/app/View/User/Theme",
            BASE_PATH . "/assets/cache",
        ];
    }

    /**
     * 把所有应当可写的目录递归调成可写状态。
     * 调用时机：安装结束 / 升级结束。owner 错位时静默失败。
     */
    public static function grantWritableDirs(): void
    {
        foreach (self::writableDirs() as $d) {
            if (!is_dir($d)) {
                @mkdir($d, self::DIR_MODE, true);
                continue;
            }
            self::makeTreeWritable($d);
        }
    }

    /**
     * 根据当前 PHP 进程用户 + 路径所有者，给出一条用户能直接复制粘贴的 shell 命令。
     * 用于"没有写入权限"的错误消息附带"怎么修"指引。
     *
     * 注意输出里的 ":" 是分隔符（user:group），用户大概率明白；为了少踩 Windows 反斜杠路径的坑，
     * 路径用单引号包起来。
     */
    public static function suggestShellFix(string $path): string
    {
        $webUser = function_exists('posix_geteuid') && function_exists('posix_getpwuid')
            ? (posix_getpwuid(posix_geteuid())['name'] ?? 'www')
            : 'www';
        $safePath = "'" . str_replace("'", "'\\''", $path) . "'";
        return "sudo chown -R {$webUser}:{$webUser} {$safePath} && sudo chmod -R u+rwX,g+rX {$safePath}";
    }

    private static function ownerLine(string $path): string
    {
        $mode = @fileperms($path);
        $perms = is_int($mode) ? sprintf('%o', $mode & 0777) : '?';
        $uid = @fileowner($path);
        $gid = @filegroup($path);
        $owner = is_int($uid) && function_exists('posix_getpwuid')
            ? (posix_getpwuid($uid)['name'] ?? (string)$uid)
            : (string)($uid ?? '?');
        $group = is_int($gid) && function_exists('posix_getgrgid')
            ? (posix_getgrgid($gid)['name'] ?? (string)$gid)
            : (string)($gid ?? '?');
        return "所有者: {$owner}({$uid})  组: {$group}({$gid})  权限: {$perms}";
    }
}
