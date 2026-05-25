<?php
declare(strict_types=1);

namespace App\Util;

use Illuminate\Database\Capsule\Manager;
use Kernel\Exception\JSONException;

/**
 * 数据库结构漂移检测：以 kernel/Install/Install.sql 为权威 schema，
 * 对比当前数据库实际表/列，找出缺失项。仅作只读检测，不修改任何数据。
 */
class SchemaDiff
{
    /**
     * @return array{
     *     ok: bool,
     *     missing_tables: array<int, string>,
     *     missing_columns: array<string, array<int, string>>,
     *     suggested_sql: array<int, string>
     * }
     */
    public static function diff(): array
    {
        $expected = self::parseInstallSql(BASE_PATH . "/kernel/Install/Install.sql");
        $actual = self::dumpCurrentSchema();

        $missingTables = [];
        $missingColumns = [];
        $suggested = [];

        foreach ($expected as $table => $info) {
            if (!isset($actual[$table])) {
                $missingTables[] = $table;
                $suggested[] = $info['create_sql'];
                continue;
            }
            $missing = array_values(array_diff($info['columns'], $actual[$table]));
            if (!empty($missing)) {
                $missingColumns[$table] = $missing;
                foreach ($missing as $col) {
                    if (isset($info['column_defs'][$col])) {
                        $suggested[] = "ALTER TABLE `{$table}` ADD COLUMN " . $info['column_defs'][$col] . ";";
                    }
                }
            }
        }

        return [
            "ok" => empty($missingTables) && empty($missingColumns),
            "missing_tables" => $missingTables,
            "missing_columns" => $missingColumns,
            "suggested_sql" => $suggested,
        ];
    }

    /**
     * 解析 Install.sql，返回 [带前缀表名 => {columns:[], column_defs:[col=>def], create_sql:str}]。
     *
     * @return array<string, array{columns: array<int, string>, column_defs: array<string, string>, create_sql: string}>
     */
    private static function parseInstallSql(string $path): array
    {
        if (!is_file($path)) {
            return [];
        }
        $prefix = (string)(((array)config("database"))['prefix'] ?? '');
        $sql = (string)file_get_contents($path);
        // 去除 /* ... */ 块注释与 -- 行注释
        $sql = preg_replace('!/\*.*?\*/!s', '', $sql) ?? $sql;
        $sql = preg_replace('/^\s*--[^\n]*$/m', '', $sql) ?? $sql;

        $tables = [];
        // 匹配每条 CREATE TABLE `__PREFIX__xxx` ( ... );
        $pattern = '/CREATE\s+TABLE\s+`__PREFIX__([A-Za-z0-9_]+)`\s*\((.*?)\)\s*ENGINE/is';
        if (!preg_match_all($pattern, $sql, $matches, PREG_SET_ORDER)) {
            return [];
        }
        foreach ($matches as $m) {
            $rawTable = $m[1];
            $body = $m[2];
            $columns = [];
            $columnDefs = [];
            // 按顶层逗号拆分（不进入括号内）
            foreach (self::splitTopLevel($body) as $line) {
                $line = trim($line);
                if ($line === '') {
                    continue;
                }
                // 跳过约束、索引、主键等
                if (preg_match('/^(PRIMARY\s+KEY|UNIQUE\s+(?:INDEX|KEY)|INDEX|KEY|CONSTRAINT|FOREIGN\s+KEY|FULLTEXT|SPATIAL)\b/i', $line)) {
                    continue;
                }
                if (preg_match('/^`([A-Za-z0-9_]+)`\s+(.+)$/s', $line, $cm)) {
                    $col = $cm[1];
                    $columns[] = $col;
                    $columnDefs[$col] = "`{$col}` " . trim($cm[2]);
                }
            }
            $tableWithPrefix = $prefix . $rawTable;
            // 生成可直接用于建表的 SQL（替换 __PREFIX__ 为实际前缀）
            $createSql = str_replace('__PREFIX__', $prefix, (string)$m[0]) . " ;";
            $tables[$tableWithPrefix] = [
                "columns" => $columns,
                "column_defs" => $columnDefs,
                "create_sql" => $createSql,
            ];
        }
        return $tables;
    }

    /**
     * 顶层逗号拆分，保留括号内字符串。
     *
     * @return array<int, string>
     */
    private static function splitTopLevel(string $body): array
    {
        $parts = [];
        $depth = 0;
        $buf = '';
        $len = strlen($body);
        for ($i = 0; $i < $len; $i++) {
            $ch = $body[$i];
            if ($ch === '(') {
                $depth++;
            } elseif ($ch === ')') {
                $depth--;
            }
            if ($ch === ',' && $depth === 0) {
                $parts[] = $buf;
                $buf = '';
                continue;
            }
            $buf .= $ch;
        }
        if (trim($buf) !== '') {
            $parts[] = $buf;
        }
        return $parts;
    }

    /**
     * 查 information_schema 列出当前库里带前缀的表及其列。
     *
     * @return array<string, array<int, string>>
     */
    private static function dumpCurrentSchema(): array
    {
        $database = (array)config("database");
        $dbName = (string)$database['database'];
        $prefix = (string)($database['prefix'] ?? '');
        if ($prefix === '') {
            return [];
        }

        try {
            $rows = Manager::connection()->select(
                "SELECT TABLE_NAME, COLUMN_NAME
                 FROM information_schema.columns
                 WHERE TABLE_SCHEMA = ? AND TABLE_NAME LIKE ?",
                [$dbName, $prefix . '%']
            );
        } catch (\Throwable $e) {
            throw new JSONException("无法查询数据库结构：" . $e->getMessage());
        }

        $out = [];
        foreach ($rows as $row) {
            $obj = (array)$row;
            $t = (string)($obj['TABLE_NAME'] ?? $obj['table_name'] ?? '');
            $c = (string)($obj['COLUMN_NAME'] ?? $obj['column_name'] ?? '');
            if ($t === '' || $c === '') {
                continue;
            }
            $out[$t] ??= [];
            $out[$t][] = $c;
        }
        return $out;
    }
}
