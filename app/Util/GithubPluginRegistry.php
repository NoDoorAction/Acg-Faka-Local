<?php
declare(strict_types=1);

namespace App\Util;

use Kernel\Exception\JSONException;

/**
 * 从 GitHub 插件仓库拉取 plugins.json 索引。
 *
 * 仓库地址由 config/app.php 的 github_plugin_owner / github_plugin_repo 配置；
 * 默认指向 NoDoorAction/Acg-Faka-Plugins。
 *
 * 拉取结果会缓存到 runtime/plugin/registry.cache（15 分钟），减少未鉴权限流压力。
 */
class GithubPluginRegistry
{
    private const CACHE_TTL = 900; // 15 分钟

    /**
     * @return array{owner: string, repo: string, branch: string, token: string}
     * @throws JSONException
     */
    public static function repo(): array
    {
        $app = (array)config("app");
        $owner = trim((string)($app['github_plugin_owner'] ?? 'NoDoorAction'));
        $repo = trim((string)($app['github_plugin_repo'] ?? 'Acg-Faka-Plugins'));
        $branch = trim((string)($app['github_plugin_branch'] ?? 'main'));
        $token = trim((string)($app['github_token'] ?? ''));
        if ($owner === '' || $repo === '') {
            throw new JSONException("未配置 github_plugin_owner / github_plugin_repo");
        }
        return ["owner" => $owner, "repo" => $repo, "branch" => $branch, "token" => $token];
    }

    /**
     * 拉取并返回 plugins.json 内容（解析为数组）。带本地缓存。
     *
     * @return array{schema_version:int, updated_at:string, items: array<int, array<string, mixed>>}
     * @throws JSONException
     */
    public static function fetch(bool $force = false): array
    {
        $cacheFile = self::cachePath();
        if (!$force && is_file($cacheFile) && (time() - (int)filemtime($cacheFile)) < self::CACHE_TTL) {
            $raw = (string)file_get_contents($cacheFile);
            $data = json_decode($raw, true);
            if (is_array($data) && isset($data['items'])) {
                return $data;
            }
        }

        $r = self::repo();
        $url = "https://raw.githubusercontent.com/{$r['owner']}/{$r['repo']}/{$r['branch']}/plugins.json";

        try {
            $headers = [
                "User-Agent" => "acg-faka-local-plugin-registry",
                "Accept" => "application/json",
            ];
            if ($r['token'] !== '') {
                $headers["Authorization"] = "Bearer {$r['token']}";
            }
            $resp = Http::make()->get($url, ["headers" => $headers, "timeout" => 15]);
            $code = $resp->getStatusCode();
            $body = (string)$resp->getBody()->getContents();
            if ($code !== 200) {
                throw new JSONException("拉取 plugins.json 失败：HTTP {$code}");
            }
            $data = json_decode($body, true);
            if (!is_array($data) || !isset($data['items']) || !is_array($data['items'])) {
                throw new JSONException("plugins.json 格式不合法");
            }
            self::writeCache($body);
            return $data;
        } catch (JSONException $e) {
            throw $e;
        } catch (\Throwable $e) {
            // 网络出错且本地有缓存，仍然返回旧缓存兜底（降级体验）
            if (is_file($cacheFile)) {
                $cached = json_decode((string)file_get_contents($cacheFile), true);
                if (is_array($cached) && isset($cached['items'])) {
                    return $cached;
                }
            }
            throw new JSONException("无法访问插件仓库：" . $e->getMessage());
        }
    }

    /**
     * @return array<string, mixed>|null
     * @throws JSONException
     */
    public static function find(string $key, int $type): ?array
    {
        $data = self::fetch();
        foreach ($data['items'] as $item) {
            if (!is_array($item)) {
                continue;
            }
            if ((string)($item['key'] ?? '') === $key && (int)($item['type'] ?? 0) === $type) {
                return $item;
            }
        }
        return null;
    }

    /**
     * 把 plugins.json 里的相对 icon 路径转为 raw.githubusercontent.com 绝对 URL。
     * 已是 http(s):// 开头的路径原样返回；空值返回 /favicon.ico 兜底。
     */
    public static function iconUrl(string $icon): string
    {
        $icon = trim($icon);
        if ($icon === '') {
            return '/favicon.ico';
        }
        if (str_contains($icon, '://')) {
            return $icon;
        }
        try {
            $r = self::repo();
            return "https://raw.githubusercontent.com/{$r['owner']}/{$r['repo']}/{$r['branch']}/" . ltrim($icon, '/');
        } catch (\Throwable $e) {
            return '/favicon.ico';
        }
    }

    public static function clearCache(): void
    {
        $f = self::cachePath();
        if (is_file($f)) {
            @unlink($f);
        }
    }

    private static function cachePath(): string
    {
        $dir = BASE_PATH . "/runtime/plugin";
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }
        return $dir . "/registry.cache";
    }

    private static function writeCache(string $json): void
    {
        @file_put_contents(self::cachePath(), $json);
    }
}
