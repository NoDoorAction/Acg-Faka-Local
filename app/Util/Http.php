<?php
declare(strict_types=1);

namespace App\Util;

use App\Entity\Store\Authentication;
use Kernel\Plugin\Plugin;
use Kernel\Util\Aes;
use Kernel\Util\Str;

/**
 *
 */
class Http
{
    /**
     * @param array $opt
     * @return \GuzzleHttp\Client
     */
    public static function make(array $opt = []): \GuzzleHttp\Client
    {
        return new \GuzzleHttp\Client(array_merge(["verify" => false], $opt));
    }

    /**
     * @param string $url
     * @param string $path
     * @param string $method
     * @param array $data
     * @return bool
     */
    public static function download(string $url, string $path, string $method = "GET", array $data = []): bool
    {
        try {
            $dir = dirname($path);
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }

            $options = [
                "verify" => false,
                "sink" => $path,
                "headers" => [
                    "User-Agent" => "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36"
                ]
            ];

            if ($method === "POST" && !empty($data)) {
                $options["form_params"] = $data;
            }

            self::make()->request($method, $url, $options);
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * 带字节级进度回调的下载。
     *
     * 回调签名：fn(int $downloaded, int $total) => void
     *  - $total = 0 表示服务端未返回 Content-Length（GitHub 重定向后通常会返回）
     *
     * 失败抛 \RuntimeException，便于上层捕获并写入 task 失败原因。
     */
    public static function downloadWithProgress(string $url, string $path, callable $onProgress): void
    {
        $dir = dirname($path);
        if (!is_dir($dir) && !@mkdir($dir, 0777, true) && !is_dir($dir)) {
            throw new \RuntimeException("无法创建下载目录: {$dir}");
        }
        $options = [
            "verify"  => false,
            "sink"    => $path,
            "timeout" => 0,
            "headers" => [
                "User-Agent" => "acg-faka-local-updater",
            ],
            "progress" => static function ($total, $downloaded /*, $ut, $ud */) use ($onProgress) {
                // Guzzle progress 触发非常频繁，调用方自行节流
                $onProgress((int)$downloaded, (int)$total);
            },
        ];
        self::make()->request("GET", $url, $options);
        if (!is_file($path) || filesize($path) === 0) {
            throw new \RuntimeException("下载完成但本地文件不存在或为空");
        }
    }
}