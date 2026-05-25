<?php
declare(strict_types=1);

namespace App\Util;

use Illuminate\Database\Capsule\Manager;
use Illuminate\Database\Schema\Blueprint;
use Kernel\Exception\JSONException;
use Kernel\Util\SQL;

class Migrator
{
    private const DIR = "/migrations";
    private const TABLE = "migration";

    /**
     * 确保 migration 表存在；若不存在则建表并把所有 <= $baselineVersion 的迁移记为已应用。
     */
    public static function ensureTable(string $baselineVersion): void
    {
        $schema = Manager::schema();
        if ($schema->hasTable(self::TABLE)) {
            return;
        }
        $schema->create(self::TABLE, function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('version', 32)->unique();
            $table->string('file', 128);
            $table->dateTime('applied_at');
        });
        self::baseline($baselineVersion);
    }

    /**
     * 将所有 <= $baselineVersion 的迁移文件记为已应用（不实际执行）。
     */
    public static function baseline(string $baselineVersion): void
    {
        $files = self::scan();
        $now = date("Y-m-d H:i:s");
        foreach ($files as $version => $file) {
            if (version_compare($version, $baselineVersion, '<=')) {
                self::recordIfAbsent($version, basename($file), $now);
            }
        }
    }

    /**
     * 返回介于（已应用最高版本, 目标版本] 之间、按版本升序排列的迁移文件路径。
     *
     * @return array<int, array{version:string, file:string}>
     */
    public static function pending(string $upToVersion): array
    {
        $applied = self::appliedVersions();
        $all = self::scan();
        $pending = [];
        foreach ($all as $version => $file) {
            if (in_array($version, $applied, true)) {
                continue;
            }
            if (version_compare($version, $upToVersion, '>')) {
                continue;
            }
            $pending[] = ["version" => $version, "file" => $file];
        }
        usort($pending, fn($a, $b) => version_compare($a['version'], $b['version']));
        return $pending;
    }

    /**
     * 依次执行迁移，每个文件成功后记入 migration 表。失败抛出 JSONException。
     *
     * @param array<int, array{version:string, file:string}> $items
     */
    public static function apply(array $items): void
    {
        if (empty($items)) {
            return;
        }
        $database = (array)config("database");
        foreach ($items as $item) {
            $file = $item['file'];
            $version = $item['version'];
            if (!is_file($file)) {
                throw new JSONException("迁移文件不存在：{$file}");
            }
            $content = (string)file_get_contents($file);
            // 空文件视为占位迁移，仍记录但跳过 SQL 执行
            if (trim($content) !== '') {
                SQL::import(
                    $file,
                    (string)$database['host'],
                    (string)$database['database'],
                    (string)$database['username'],
                    (string)$database['password'],
                    (string)$database['prefix']
                );
            }
            self::recordIfAbsent($version, basename($file), date("Y-m-d H:i:s"));
        }
    }

    /**
     * 一站式：建表 / baseline / 应用至 $upToVersion。
     */
    public static function migrate(string $currentVersion, string $upToVersion): array
    {
        self::ensureTable($currentVersion);
        $pending = self::pending($upToVersion);
        self::apply($pending);
        return $pending;
    }

    /**
     * 扫描 migrations 目录，返回 [version => fullPath]，按版本升序。
     *
     * @return array<string, string>
     */
    private static function scan(): array
    {
        $dir = BASE_PATH . self::DIR;
        if (!is_dir($dir)) {
            return [];
        }
        $out = [];
        foreach ((array)scandir($dir) as $name) {
            if (!is_string($name) || $name === '.' || $name === '..') {
                continue;
            }
            if (!preg_match('/^([0-9]+(?:\.[0-9]+)+(?:[-+][A-Za-z0-9.]+)?)\.sql$/', $name, $m)) {
                continue;
            }
            $out[$m[1]] = $dir . DIRECTORY_SEPARATOR . $name;
        }
        uksort($out, fn($a, $b) => version_compare($a, $b));
        return $out;
    }

    /**
     * @return array<int, string>
     */
    private static function appliedVersions(): array
    {
        $rows = Manager::table(self::TABLE)->pluck('version')->all();
        $out = [];
        foreach ($rows as $v) {
            $out[] = (string)$v;
        }
        return $out;
    }

    private static function recordIfAbsent(string $version, string $file, string $appliedAt): void
    {
        $exists = Manager::table(self::TABLE)->where('version', $version)->exists();
        if ($exists) {
            return;
        }
        Manager::table(self::TABLE)->insert([
            'version' => $version,
            'file' => $file,
            'applied_at' => $appliedAt,
        ]);
    }
}
