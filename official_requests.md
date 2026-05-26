# 远程请求白名单

> **本仓库自 3.5.3 起完全脱离原异次元应用商店，所有"上报站点域名 / 客户端 IP / 安装事件 / 拉取广告公告"等行为已删除。**
> 本文档列出当前版本（3.5.3+）**仅有**的对外网络请求，确保用户能审计本程序的网络行为。

## 全部远程请求清单

| # | 目的地 | 触发 | 携带数据 | 是否可关闭 |
|---|--------|------|----------|-----------|
| 1 | `api.github.com` | 后台 → 检查更新 / 升级主程序 | GitHub 仓库 owner/repo 路径，无站点身份信息 | 不检查更新即不请求 |
| 2 | `raw.githubusercontent.com` | 后台 → 应用商店列表 / 安装/升级插件 | 仓库内文件路径 | 不打开应用商店即不请求 |
| 3 | `api.github.com` | 同上（Trees API，按目录抓文件时用） | 仓库内目录路径 | 同上 |
| 4 | 用户配置的 SMTP 服务器 | 用户在后台配置邮件通知后，按业务事件发邮件 | 由用户自己填写 | 不配置即不请求 |
| 5 | 用户配置的 SMS 服务商（如 SmsBao） | 用户在后台启用短信通知插件后 | 由用户自己填写 | 不启用即不请求 |
| 6 | 用户配置的支付通道 (Alipay / 微信 / USDT / Stripe / PayPal / Epay 等) | 用户下单 / 商户回调 | 由用户自己填写 | 不启用对应支付通道即不请求 |

> 1-3 由 `config/app.php` 的 `github_owner` / `github_repo` / `github_plugin_owner` / `github_plugin_repo` 控制，全部指向公开 GitHub 仓库，没有身份鉴权（除非配置可选 `github_token`）。
> 4-6 是业务功能必需的对外通信，目标 host / 凭据均由用户在后台自行填写。

## 已删除的旧请求

历史版本（3.4.x 及更早）会向以下地址发送请求，**3.5.3 起这些代码已全部清空**：

- `https://tencent.3rd.mcycdn.com`
- `https://byte.3rd.mcycdn.com`
- `https://standby.acgshe.com`
- `https://aliyun.3rd.mcycdn.com`

对应已删除的功能：

| 旧接口 | 旧行为 | 当前替代 |
|--------|--------|----------|
| `/open/project/version` | 上报站点域名 / 客户端 IP 检查版本 | GitHub Releases (`Github::latestRelease()`) |
| `/open/project/install` | 上报新站点安装事件 | 已删除，本地化版本不上报 |
| `/open/project/ad` | 拉取后台公告 / 广告 | 已删除，前端固定显示"暂无公告" |
| `/auth/captcha` `/auth/login` `/auth/register` | 应用商店账号体系 | 已删除 |
| `/store/plugins` `/store/install` `/store/update` `/store/purchase` `/store/records` `/store/unbind` `/store/bindLevel` `/store/levels` `/store/service` `/store/editPassword` | 应用商店浏览/安装/购买/授权 | GitHub 插件仓库 (`NoDoorAction/Acg-Faka-Plugins`) |
| `/developer/*` | 开发者中心创建/上架插件 | 改为提 GitHub PR |
| `/open/project/upload` | 上传插件 zip 到官方审核 | 已删除 |

## 如何验证

1. **抓包验证**：站点正常运行时,对外 TCP 连接应当**只**指向上表所列 host。
2. **代码 grep**：在仓库根执行 `grep -rE "mcycdn|acgshe" --include="*.php" --include="*.js"`，应该**没有任何代码层引用**（只有本文档和 git 历史里有提及）。
3. **抓代理**：把环境变量 `HTTP_PROXY=http://127.0.0.1:8888` 接到 mitmproxy，访问后台首页、应用商店页、不操作升级，不应有任何旧域名请求。

## 配置默认值

`config/app.php` 中的 GitHub 端点配置：

```php
'github_owner'         => 'NoDoorAction',
'github_repo'          => 'Acg-Faka-Local',
'github_token'         => '',         // 可选，应对未鉴权 60/hr 限流
'github_plugin_owner'  => 'NoDoorAction',
'github_plugin_repo'   => 'Acg-Faka-Plugins',
'github_plugin_branch' => 'main',
```

可以改为任何你 fork 的仓库或私有 GitHub Enterprise 地址，**完全自主控制**。
