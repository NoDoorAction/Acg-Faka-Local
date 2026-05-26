<?php
declare(strict_types=1);

/**
 * 应用商店 / 主程序升级的 GitHub 镜像线路注册表。
 *
 * 每个镜像声明：
 *   - name        显示名（中文，UI 列表用）
 *   - api         api.github.com 的替换前缀（空字符串 = 直连 https://api.github.com）
 *   - raw         raw.githubusercontent.com 的替换前缀（空字符串 = 直连）
 *                 也支持特殊值 "jsdelivr" → 走 Mirror::rewriteRawAsJsdelivr 的特殊格式改写
 *   - asset       github.com/releases/download/* 的替换前缀（用于 release 资源下载）
 *   - ping        测延迟时拉取的 URL；HEAD 一次小资源即可
 *   - desc        UI 上展示给用户看的一行说明
 *   - api_supported 是否能代理 api.github.com 调用（false 时 api 仍走直连）
 *
 * 默认线路是 direct（GitHub 直连）。用户在后台 → 头部 → 线路切换里选择。
 * 自定义线路单独走 custom_mirror 字段，不出现在本列表里。
 *
 * 镜像选择是个动态的事情，公网开源代理来来去去，本列表只保留几个相对稳定的；
 * 用户如果踩到不可用，应该用"自定义线路"选项填自己的代理前缀。
 */
return [
    'direct' => [
        'name'          => 'GitHub 直连',
        'api'           => 'https://api.github.com',
        'raw'           => 'https://raw.githubusercontent.com',
        'asset'         => 'https://github.com',
        'ping'          => 'https://api.github.com/zen',
        'desc'          => '不经任何代理，国外服务器或已配置 V2Ray/Clash 等代理时最快',
        'api_supported' => true,
    ],
    'ghproxy' => [
        'name'          => 'ghproxy.com',
        'api'           => 'https://ghproxy.com/https://api.github.com',
        'raw'           => 'https://ghproxy.com/https://raw.githubusercontent.com',
        'asset'         => 'https://ghproxy.com/https://github.com',
        'ping'          => 'https://ghproxy.com/',
        'desc'          => '通用 GitHub 反代，国内可用，偶尔不稳定',
        'api_supported' => true,
    ],
    'ghfast' => [
        'name'          => 'ghfast.top',
        'api'           => 'https://ghfast.top/https://api.github.com',
        'raw'           => 'https://ghfast.top/https://raw.githubusercontent.com',
        'asset'         => 'https://ghfast.top/https://github.com',
        'ping'          => 'https://ghfast.top/',
        'desc'          => '速度较快的 GitHub 反代，国内体验通常优于 ghproxy',
        'api_supported' => true,
    ],
    'gh-proxy' => [
        'name'          => 'gh-proxy.com',
        'api'           => 'https://gh-proxy.com/https://api.github.com',
        'raw'           => 'https://gh-proxy.com/https://raw.githubusercontent.com',
        'asset'         => 'https://gh-proxy.com/https://github.com',
        'ping'          => 'https://gh-proxy.com/',
        'desc'          => '备用 GitHub 反代',
        'api_supported' => true,
    ],
    'jsdelivr' => [
        'name'          => 'jsDelivr CDN（仅 raw 与 release 资源）',
        'api'           => '',              // jsDelivr 不代理 api，自动回退直连
        'raw'           => 'jsdelivr',      // 特殊标记，由 Mirror::rewriteRaw 处理 URL 形态
        'asset'         => '',              // release zip 不走 jsDelivr（它不缓存大于 ~20MB 的文件）
        'ping'          => 'https://cdn.jsdelivr.net/gh/jsdelivr/static/css/style.css',
        'desc'          => 'jsDelivr 全球 CDN，仅加速 raw 文件；api 调用仍走直连',
        'api_supported' => false,
    ],
];
