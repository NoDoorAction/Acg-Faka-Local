<?php
declare(strict_types=1);

namespace App\Util;

use Kernel\Exception\JSONException;

/**
 * 从 GitHub 插件仓库下载单个插件到目标本地目录。
 *
 * 两种模式：
 * 1. plugins.json item 提供 `download_url` → 直接下整包 zip 然后解压；
 * 2. 否则用 Git Trees API 列出 `path` 下所有文件，逐个走 raw.githubusercontent.com 下载。
 *
 * 后者每装一个插件会消耗 1 + N 个 GitHub API 调用（N = 文件数），未鉴权 60/hr。
 * 如果常用插件较大、文件数较多，建议在 plugins.json 里提供 download_url 走模式 1。
 */
class GithubPluginDownloader
{
    /**
     * 下载并解压一个插件目录到指定本地目录。
     *
     * @param array<string, mixed> $item plugins.json 中的某个 item
     * @param string $targetDir 本地目标目录（如 BASE_PATH . "/app/Plugin/GoTop/"）
     * @throws JSONException
     */
    public static function download(array $item, string $targetDir): void
    {
        $targetDir = rtrim($targetDir, "/\\") . DIRECTORY_SEPARATOR;
        if (!is_dir($targetDir) && !@mkdir($targetDir, 0777, true) && !is_dir($targetDir)) {
            throw new JSONException("无法创建目标目录：{$targetDir}");
        }

        $downloadUrl = (string)($item['download_url'] ?? '');
        if ($downloadUrl !== '') {
            self::downloadAsZip($downloadUrl, $targetDir);
            return;
        }

        $path = trim((string)($item['path'] ?? ''), "/");
        if ($path === '') {
            throw new JSONException("插件元数据缺少 path 字段");
        }
        self::downloadViaTreesApi($path, $targetDir);
    }

    /**
     * 模式 1：直接下整包 zip。
     */
    private static function downloadAsZip(string $url, string $targetDir): void
    {
        $tmp = BASE_PATH . "/runtime/plugin/dl-" . substr(md5((string)mt_rand()), 0, 8) . ".zip";
        @mkdir(dirname($tmp), 0777, true);
        if (!Http::download($url, $tmp)) {
            throw new JSONException("插件 zip 下载失败：{$url}");
        }
        if (!Zip::unzip($tmp, $targetDir)) {
            @unlink($tmp);
            throw new JSONException("插件 zip 解压失败");
        }
        @unlink($tmp);

        // 单一顶层子目录时下钻一层
        $children = array_values(array_filter((array)scandir($targetDir) ?: [], fn($n) => is_string($n) && $n !== '.' && $n !== '..'));
        if (count($children) === 1 && is_dir($targetDir . $children[0])) {
            self::flatten($targetDir . $children[0], $targetDir);
        }
    }

    /**
     * 模式 2：用 Trees API 列文件 + raw.githubusercontent.com 逐个下。
     */
    private static function downloadViaTreesApi(string $path, string $targetDir): void
    {
        $r = GithubPluginRegistry::repo();
        $branch = $r['branch'];

        $treeUrl = Mirror::api("/repos/{$r['owner']}/{$r['repo']}/git/trees/{$branch}?recursive=1");
        $tree = self::apiGet($treeUrl, $r['token']);
        if (!isset($tree['tree']) || !is_array($tree['tree'])) {
            throw new JSONException("Trees API 返回数据格式错误");
        }
        if (!empty($tree['truncated'])) {
            // 仓库太大被截断时，目前不支持分页 fallback；建议提供 download_url
            throw new JSONException("仓库文件数过多，Trees API 返回被截断。请在 plugins.json 中为该插件提供 download_url");
        }

        $prefix = $path . '/';
        $files = [];
        foreach ($tree['tree'] as $node) {
            if (!is_array($node)) continue;
            $p = (string)($node['path'] ?? '');
            $type = (string)($node['type'] ?? '');
            if ($type !== 'blob') continue;
            if (!str_starts_with($p, $prefix) && $p !== $path) continue;
            $files[] = [
                'path' => $p,
                'relative' => substr($p, strlen($prefix)),
                'size' => (int)($node['size'] ?? 0),
            ];
        }

        if (empty($files)) {
            throw new JSONException("仓库目录 {$path} 下没有任何文件");
        }

        foreach ($files as $f) {
            $relative = $f['relative'];
            if ($relative === '' || $relative === false) continue;
            $local = $targetDir . str_replace('/', DIRECTORY_SEPARATOR, $relative);
            $dir = dirname($local);
            if (!is_dir($dir) && !@mkdir($dir, 0777, true) && !is_dir($dir)) {
                throw new JSONException("无法创建子目录：{$dir}");
            }
            $rawUrl = Mirror::raw($r['owner'], $r['repo'], $branch, $f['path']);
            if (!Http::download($rawUrl, $local)) {
                throw new JSONException("文件下载失败：{$rawUrl}");
            }
        }
    }

    /**
     * 把 $src 目录内所有内容移动到 $dst，然后删除 $src。
     */
    private static function flatten(string $src, string $dst): void
    {
        $items = (array)scandir($src) ?: [];
        foreach ($items as $name) {
            if (!is_string($name) || $name === '.' || $name === '..') continue;
            @rename($src . DIRECTORY_SEPARATOR . $name, $dst . $name);
        }
        @rmdir($src);
    }

    /**
     * GitHub API GET（带可选 token，statusCode 校验）。
     *
     * @return array<string, mixed>
     * @throws JSONException
     */
    private static function apiGet(string $url, string $token = ''): array
    {
        try {
            $headers = [
                "Accept" => "application/vnd.github+json",
                "X-GitHub-Api-Version" => "2022-11-28",
                "User-Agent" => "acg-faka-local-plugin-downloader",
            ];
            if ($token !== '') {
                $headers["Authorization"] = "Bearer {$token}";
            }
            $resp = Http::make()->get($url, ["headers" => $headers, "timeout" => 15]);
            $code = $resp->getStatusCode();
            if ($code === 403) {
                throw new JSONException("GitHub API 限流（403），请在 config/app.php 配置 github_token");
            }
            if ($code !== 200) {
                throw new JSONException("GitHub API 错误：HTTP {$code}");
            }
            $body = (string)$resp->getBody()->getContents();
            $data = json_decode($body, true);
            if (!is_array($data)) {
                throw new JSONException("GitHub API 响应解析失败");
            }
            return $data;
        } catch (JSONException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new JSONException("调用 GitHub API 失败：" . $e->getMessage());
        }
    }
}
