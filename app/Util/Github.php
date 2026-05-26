<?php
declare(strict_types=1);

namespace App\Util;

use Kernel\Exception\JSONException;

class Github
{
    /** 列表 / 最新 release 的本地缓存 TTL（秒）。镜像切换会主动清缓存 */
    private const CACHE_TTL = 30 * 60;
    private const CACHE_LIST   = 'releases.cache';
    private const CACHE_LATEST = 'latest.cache';

    /**
     * @return array{owner: string, repo: string, token: string}
     */
    private static function repo(): array
    {
        $app = (array)config("app");
        $owner = trim((string)($app['github_owner'] ?? ''));
        $repo = trim((string)($app['github_repo'] ?? ''));
        $token = trim((string)($app['github_token'] ?? ''));
        if ($owner === '' || $repo === '') {
            throw new JSONException("未配置 github_owner / github_repo，请先在 config/app.php 中设置");
        }
        return ["owner" => $owner, "repo" => $repo, "token" => $token];
    }

    /**
     * @return array<int, array<string, mixed>>
     * @throws JSONException
     */
    public static function listReleases(bool $force = false): array
    {
        $cacheFile = self::cachePath(self::CACHE_LIST);
        if (!$force && is_file($cacheFile) && (time() - (int)filemtime($cacheFile)) < self::CACHE_TTL) {
            $data = json_decode((string)file_get_contents($cacheFile), true);
            if (is_array($data)) return $data;
        }

        $r = self::repo();
        $url = Mirror::api("/repos/{$r['owner']}/{$r['repo']}/releases?per_page=30");
        $data = self::get($url, $r['token']);
        if (!is_array($data)) {
            return [];
        }
        $list = [];
        foreach ($data as $item) {
            if (!is_array($item) || !empty($item['draft'])) {
                continue;
            }
            $list[] = self::shape($item);
        }
        @file_put_contents($cacheFile, json_encode($list, JSON_UNESCAPED_UNICODE));
        return $list;
    }

    /**
     * @throws JSONException
     */
    public static function latestRelease(bool $force = false): ?array
    {
        $cacheFile = self::cachePath(self::CACHE_LATEST);
        if (!$force && is_file($cacheFile) && (time() - (int)filemtime($cacheFile)) < self::CACHE_TTL) {
            $raw = (string)file_get_contents($cacheFile);
            if ($raw === 'NULL') return null;
            $data = json_decode($raw, true);
            if (is_array($data)) return $data;
        }

        $r = self::repo();
        $url = Mirror::api("/repos/{$r['owner']}/{$r['repo']}/releases/latest");
        try {
            $data = self::get($url, $r['token']);
            if (is_array($data) && !empty($data['tag_name'])) {
                $shaped = self::shape($data);
                @file_put_contents($cacheFile, json_encode($shaped, JSON_UNESCAPED_UNICODE));
                return $shaped;
            }
        } catch (JSONException $e) {
            // 仓库可能没有正式 release，fallback 到 tags
        }
        $tagsUrl = Mirror::api("/repos/{$r['owner']}/{$r['repo']}/tags?per_page=1");
        $tags = self::get($tagsUrl, $r['token']);
        if (is_array($tags) && !empty($tags[0]['name'])) {
            $tag = $tags[0];
            $shaped = [
                "tag" => (string)$tag['name'],
                "version" => self::normalizeVersion((string)$tag['name']),
                "name" => (string)$tag['name'],
                "body" => "",
                "published_at" => "",
                "zipball_url" => (string)($tag['zipball_url'] ?? ""),
                "html_url" => "https://github.com/{$r['owner']}/{$r['repo']}/releases/tag/" . urlencode((string)$tag['name']),
                "prerelease" => false,
                "assets" => [],
            ];
            @file_put_contents($cacheFile, json_encode($shaped, JSON_UNESCAPED_UNICODE));
            return $shaped;
        }
        @file_put_contents($cacheFile, 'NULL');
        return null;
    }

    public static function clearCache(): void
    {
        foreach ([self::CACHE_LIST, self::CACHE_LATEST] as $f) {
            $p = self::cachePath($f);
            if (is_file($p)) @unlink($p);
        }
    }

    private static function cachePath(string $name): string
    {
        $dir = BASE_PATH . '/runtime/plugin';
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }
        return $dir . '/' . $name;
    }

    /**
     * 选择 release 的下载 URL。
     *
     * 优先级（从高到低）：
     *   1. 文件名含 "overlay" 的 zip asset —— 覆盖包，已剔除 config/database.php、
     *      config/store.php、runtime/、kernel/Install/Lock 等，可直接解压覆盖到运行中的站点
     *   2. 其它 .zip asset —— 一般是带 vendor 的完整包
     *   3. zipball_url —— GitHub 自动生成的源码 zip（最低优先，因为含 .git 元数据外壳，
     *      还可能在 GitHub 临时存储里抖动）
     *
     * 这样一旦发布者上传了 overlay zip，用户的"一键升级"会自动用它，不需要手动选。
     */
    public static function pickDownloadUrl(array $release): string
    {
        $assets = (array)($release['assets'] ?? []);
        if ($assets) {
            // 第一遍：找 overlay
            foreach ($assets as $asset) {
                if (!is_array($asset)) continue;
                $name = strtolower((string)($asset['name'] ?? ''));
                $url = (string)($asset['browser_download_url'] ?? '');
                if ($url === '' || !str_ends_with($name, '.zip')) continue;
                if (str_contains($name, 'overlay')) {
                    return Mirror::asset($url);
                }
            }
            // 第二遍：任意 zip
            foreach ($assets as $asset) {
                if (!is_array($asset)) continue;
                $name = strtolower((string)($asset['name'] ?? ''));
                $url = (string)($asset['browser_download_url'] ?? '');
                if ($url !== '' && str_ends_with($name, '.zip')) {
                    return Mirror::asset($url);
                }
            }
        }
        // zipball 走 github.com，也走镜像 asset 改写
        return Mirror::asset((string)($release['zipball_url'] ?? ''));
    }

    /**
     * 判断 release 是否携带 overlay zip asset。
     * UI 用于标注"覆盖包就绪"徽标。
     */
    public static function hasOverlayAsset(array $release): bool
    {
        foreach ((array)($release['assets'] ?? []) as $asset) {
            if (!is_array($asset)) continue;
            $name = strtolower((string)($asset['name'] ?? ''));
            if (str_ends_with($name, '.zip') && str_contains($name, 'overlay')) {
                return true;
            }
        }
        return false;
    }

    public static function normalizeVersion(string $tag): string
    {
        $tag = trim($tag);
        if ($tag !== '' && ($tag[0] === 'v' || $tag[0] === 'V')) {
            $tag = substr($tag, 1);
        }
        return $tag;
    }

    /**
     * @param array<string, mixed> $item
     * @return array<string, mixed>
     */
    private static function shape(array $item): array
    {
        $tag = (string)($item['tag_name'] ?? '');
        $assets = [];
        foreach ((array)($item['assets'] ?? []) as $asset) {
            if (!is_array($asset)) {
                continue;
            }
            $assets[] = [
                "name" => (string)($asset['name'] ?? ''),
                "browser_download_url" => (string)($asset['browser_download_url'] ?? ''),
                "size" => (int)($asset['size'] ?? 0),
            ];
        }
        return [
            "tag" => $tag,
            "version" => self::normalizeVersion($tag),
            "name" => (string)($item['name'] ?? $tag),
            "body" => (string)($item['body'] ?? ''),
            "published_at" => (string)($item['published_at'] ?? ''),
            "zipball_url" => (string)($item['zipball_url'] ?? ''),
            "html_url" => (string)($item['html_url'] ?? ''),
            "prerelease" => (bool)($item['prerelease'] ?? false),
            "assets" => $assets,
        ];
    }

    /**
     * @throws JSONException
     */
    private static function get(string $url, string $token = ""): mixed
    {
        try {
            $headers = [
                "Accept" => "application/vnd.github+json",
                "X-GitHub-Api-Version" => "2022-11-28",
                "User-Agent" => "acg-faka-local-updater",
            ];
            if ($token !== '') {
                $headers["Authorization"] = "Bearer {$token}";
            }
            $resp = Http::make()->get($url, [
                "headers" => $headers,
                "timeout" => 15,
            ]);
            $body = (string)$resp->getBody()->getContents();
            $code = $resp->getStatusCode();
            if ($code === 403) {
                throw new JSONException("GitHub 接口请求被限流（403），请在 config/app.php 配置 github_token");
            }
            if ($code === 404) {
                throw new JSONException("GitHub 仓库或资源不存在（404），请检查 github_owner / github_repo");
            }
            if ($code >= 400) {
                throw new JSONException("GitHub 接口返回错误：HTTP {$code}");
            }
            $json = json_decode($body, true);
            if (!is_array($json)) {
                throw new JSONException("GitHub 接口响应解析失败");
            }
            return $json;
        } catch (JSONException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new JSONException("无法连接 GitHub：" . $e->getMessage());
        }
    }
}
