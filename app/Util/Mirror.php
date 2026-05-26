<?php
declare(strict_types=1);

namespace App\Util;

use Kernel\Exception\JSONException;

/**
 * GitHub 镜像线路管理。
 *
 * 设计：
 * - 镜像列表来自 config/mirrors.php（硬编码白名单）
 * - 用户选择存在 config/store.php 的 server / custom_mirror 字段（沿用旧字段名，省一次迁移）
 *   - server: 字符串 key，'direct' / 'ghproxy' / 'ghfast' / 'gh-proxy' / 'jsdelivr' / 'custom'
 *   - custom_mirror: server=custom 时使用，结构 ['api'=>..., 'raw'=>..., 'asset'=>...]
 * - 所有走 GitHub 的代码（Github / GithubPluginRegistry / GithubPluginDownloader）
 *   都不再直接拼 URL，而是调 Mirror::api(path) / Mirror::raw(...) / Mirror::asset(...)
 */
class Mirror
{
    /** 旧 config/store.php 兼容键（保留 server 字段名） */
    private const STORE_KEY_ACTIVE = 'server';
    private const STORE_KEY_CUSTOM = 'custom_mirror';

    /** @var array<string, array<string, mixed>>|null */
    private static ?array $registry = null;

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function registry(): array
    {
        if (self::$registry === null) {
            self::$registry = (array)config('mirrors');
        }
        return self::$registry;
    }

    /**
     * 当前选中的镜像 key。无配置时默认 direct。
     */
    public static function activeKey(): string
    {
        $store = (array)config('store');
        $key = (string)($store[self::STORE_KEY_ACTIVE] ?? 'direct');
        $registry = self::registry();
        if ($key !== 'custom' && !isset($registry[$key])) {
            return 'direct';
        }
        return $key;
    }

    /**
     * 当前镜像的配置（合并 registry + 用户 custom 字段）。
     *
     * @return array{name:string, api:string, raw:string, asset:string, ping:string, desc:string, api_supported:bool}
     */
    public static function active(): array
    {
        $key = self::activeKey();
        if ($key === 'custom') {
            $store = (array)config('store');
            $custom = (array)($store[self::STORE_KEY_CUSTOM] ?? []);
            return self::normalizeCustom($custom);
        }
        $registry = self::registry();
        return $registry[$key] ?? $registry['direct'];
    }

    /**
     * 把 api.github.com 的相对路径变成完整 URL（自动应用当前镜像前缀）。
     * 如果当前镜像 api_supported=false（如 jsDelivr），自动回退直连。
     */
    public static function api(string $apiPath): string
    {
        $m = self::active();
        $base = (string)$m['api'];
        if ($base === '' || !($m['api_supported'] ?? false)) {
            $base = 'https://api.github.com';
        }
        return rtrim($base, '/') . '/' . ltrim($apiPath, '/');
    }

    /**
     * raw.githubusercontent.com 上的 {owner}/{repo}/{branch}/{path} → 实际下载 URL。
     */
    public static function raw(string $owner, string $repo, string $branch, string $path): string
    {
        $m = self::active();
        $base = (string)$m['raw'];
        $path = ltrim($path, '/');
        // jsDelivr 用 @branch 语法，不是 /branch/
        if ($base === 'jsdelivr') {
            return "https://cdn.jsdelivr.net/gh/{$owner}/{$repo}@{$branch}/{$path}";
        }
        if ($base === '') {
            $base = 'https://raw.githubusercontent.com';
        }
        return rtrim($base, '/') . "/{$owner}/{$repo}/{$branch}/{$path}";
    }

    /**
     * 把一个 GitHub release asset 下载 URL（形如 https://github.com/{o}/{r}/releases/download/{tag}/{name}）
     * 改写到当前镜像。无 asset 前缀（jsDelivr）时返回原 URL（直连）。
     */
    public static function asset(string $url): string
    {
        if ($url === '') {
            return $url;
        }
        $m = self::active();
        $base = (string)$m['asset'];
        if ($base === '') {
            return $url;
        }
        // 仅替换以 https://github.com 开头的 release 资源 URL
        if (str_starts_with($url, 'https://github.com/')) {
            return rtrim($base, '/') . substr($url, strlen('https://github.com'));
        }
        // 已经是镜像 URL 或 zipball_url 等其它形态：原样返回
        return $url;
    }

    /**
     * 给所有已知镜像做一次并发 HEAD ping，返回 [key => ['ok'=>bool, 'latency'=>int(ms), 'http'=>int|null]]。
     *
     * 不抛异常；任何错误 → ok=false latency=-1。整体耗时受 connect_timeout 限制（约 4s）。
     */
    public static function pingAll(): array
    {
        $client = Http::make(['timeout' => 5, 'connect_timeout' => 3, 'http_errors' => false]);
        $promises = [];
        $startTimes = [];
        foreach (self::registry() as $key => $m) {
            $url = (string)$m['ping'];
            if ($url === '') continue;
            $startTimes[$key] = microtime(true);
            $promises[$key] = $client->requestAsync('HEAD', $url, [
                'verify'  => false,
                'headers' => ['User-Agent' => 'acg-faka-local-mirror-ping'],
            ]);
        }

        $settled = \GuzzleHttp\Promise\Utils::settle($promises)->wait();
        $out = [];
        foreach ($settled as $key => $result) {
            $latency = (int)round((microtime(true) - $startTimes[$key]) * 1000);
            if (($result['state'] ?? '') === 'fulfilled') {
                /** @var \Psr\Http\Message\ResponseInterface $resp */
                $resp = $result['value'];
                $out[$key] = ['ok' => true, 'latency' => $latency, 'http' => $resp->getStatusCode()];
            } else {
                $reason = $result['reason'] ?? null;
                $err = $reason instanceof \Throwable ? $reason->getMessage() : 'unknown';
                $out[$key] = ['ok' => false, 'latency' => -1, 'error' => mb_substr($err, 0, 120)];
            }
        }
        return $out;
    }

    /**
     * 持久化用户选择。$key='custom' 时 $custom 至少要包含合法的 api/raw/asset URL。
     *
     * @param array{api?:string, raw?:string, asset?:string} $custom
     * @throws JSONException
     */
    public static function setActive(string $key, array $custom = []): void
    {
        if ($key === 'custom') {
            $norm = self::normalizeCustom($custom);
            if (!self::looksLikeUrl((string)$norm['api']) || !self::looksLikeUrl((string)$norm['raw'])) {
                throw new JSONException('自定义线路至少需要填写合法的 api / raw URL（http(s):// 开头）');
            }
        } else {
            $registry = self::registry();
            if (!isset($registry[$key])) {
                throw new JSONException("未知的镜像线路：{$key}");
            }
        }

        $store = (array)config('store');
        $store[self::STORE_KEY_ACTIVE] = $key;
        if ($key === 'custom') {
            $store[self::STORE_KEY_CUSTOM] = self::normalizeCustom($custom);
        }
        $path = BASE_PATH . '/config/store.php';
        setConfig($store, $path);
        Opcache::invalidate($path);

        // 镜像换了，旧的 releases / plugins.json 缓存可能内容相同但下载源不同，清掉重拉更稳
        self::clearGithubCaches();
    }

    /**
     * 清掉所有 GitHub 相关的文件缓存，强制下次重新拉。
     */
    public static function clearGithubCaches(): void
    {
        $dir = BASE_PATH . '/runtime/plugin';
        foreach (['registry.cache', 'releases.cache', 'latest.cache'] as $f) {
            $p = $dir . '/' . $f;
            if (is_file($p)) @unlink($p);
        }
    }

    /**
     * 归一化自定义线路配置：补齐缺失字段、去掉末尾斜杠。
     *
     * @param array<string, mixed> $custom
     * @return array<string, mixed>
     */
    private static function normalizeCustom(array $custom): array
    {
        $trim = static fn($v) => rtrim(trim((string)$v), '/');
        return [
            'name'          => '自定义线路',
            'api'           => $trim($custom['api'] ?? ''),
            'raw'           => $trim($custom['raw'] ?? ''),
            'asset'         => $trim($custom['asset'] ?? ''),
            'ping'          => $trim($custom['api'] ?? $custom['raw'] ?? ''),
            'desc'          => $custom['desc'] ?? '用户自定义的 GitHub 镜像前缀',
            'api_supported' => $trim($custom['api'] ?? '') !== '',
        ];
    }

    private static function looksLikeUrl(string $url): bool
    {
        return (bool)preg_match('#^https?://[A-Za-z0-9.\-]+[A-Za-z0-9](?::\d+)?(/.*)?$#i', $url);
    }
}
