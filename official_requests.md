# 官方请求敏感项说明

以下只列应用商店官方服务器相关请求，并说明用途与触发场景，避免误会。

官方服务器地址由 `config("store")['server']` 选择：

- `https://tencent.3rd.mcycdn.com`
- `https://byte.3rd.mcycdn.com`
- `https://standby.acgshe.com`
- `https://aliyun.3rd.mcycdn.com`

位置：`/app/Service/App.php:18`、`/kernel/Kernel.php:23`

## 1. 站点域名和客户端 IP

用途：主程序版本检查、站点识别、部署来源识别。

触发场景：后台检查版本、版本更新相关接口调用 `getVersions()` 时发生。安装流程中也可能调用版本检查。

字段：

- `key=faka`
- `domain`：当前站点域名
- `client_ip`：当前客户端 IP

接口：`/open/project/version`

位置：`/app/Service/Bind/App.php:130`、`/app/Controller/Admin/Api/App.php:25`

## 2. 安装事件

用途：通知官方服务器当前项目发生安装事件。

触发场景：安装流程调用应用安装上报时发生。

字段：

- `key=faka`

接口：`/open/project/install`

位置：`/app/Service/Bind/App.php:390`

## 3. 后台广告 / 公告

用途：从官方服务器拉取后台仪表盘广告或公告内容。

触发场景：管理员进入后台仪表盘时，前端请求 `/admin/api/app/ad`，后端再请求官方接口。

字段：

- `key=faka`

接口：`/open/project/ad`

返回内容会被前端拼进后台页面，包含标题、时间、链接等字段。

位置：`/app/Service/Bind/App.php:382`、`/app/Controller/Admin/Api/App.php:54`、`/assets/admin/controller/dashboard/index.js:10`

## 4. 商店验证码

用途：应用商店登录 / 注册前获取验证码。

触发场景：后台应用商店登录或注册页面请求验证码时调用。

字段：

- `type`

接口：`/auth/captcha`

额外行为：会读取官方响应的 `Set-Cookie`，保存 `GOLANG_ID` 供验证码流程继续使用。

位置：`/app/Service/Bind/App.php:400`、`/app/Controller/Admin/Api/App.php:75`

## 5. 商店账号注册

用途：注册应用商店账号，并把返回的 `app_id`、`app_key` 写入本地 `config/store.php`。

触发场景：只有后台应用商店注册时调用。

字段：

- `username`
- `password`
- `captcha`
- `cookie`

接口：`/auth/register`

位置：`/app/Service/Bind/App.php:418`、`/app/Controller/Admin/Api/App.php:85`

## 6. 商店账号登录

用途：登录应用商店账号，并把返回的 `app_id`、`app_key` 写入本地 `config/store.php`。

触发场景：只有后台应用商店登录时调用。

字段：

- `username`
- `password`

接口：`/auth/login`

位置：`/app/Service/Bind/App.php:430`、`/app/Controller/Admin/Api/App.php:102`

## 7. 商店授权请求头 `appId` 和 `appKey`

用途：应用商店授权、插件购买、插件安装、开发者中心、解绑授权、等级绑定等官方商店接口鉴权。

触发场景：所有通过 `storeRequest()` 和 `storeDownload()` 发往官方商店的请求都会携带。

字段：

- HTTP Header `appId`：本地 `config/store.php` 中的 `app_id`
- HTTP Header `appKey`：`_plugin_get_hwid()` 生成的本机 HWID
- 表单字段 `sign`：用请求参数和本地 `app_key` 生成签名

位置：`/app/Service/Bind/App.php:78`、`/app/Service/Bind/App.php:101`

## 8. 插件市场列表 / 查询条件

用途：拉取应用商店插件列表。

触发场景：打开后台“应用商店 -> 插件市场”、分页、搜索、筛选插件时调用。

字段：由前端列表页提交，通常包括分页、关键字、类型等查询条件。

接口：`/store/plugins`

位置：`/app/Service/Bind/App.php:442`、`/app/Controller/Admin/Api/App.php:119`

## 9. 购买应用商店插件

用途：购买插件或服务，并生成跳转回本站后台的回调地址。

触发场景：在后台应用商店点击购买时调用。

字段：

- `type`
- `payType`
- `plugin_id`
- `return`：本站后台返回地址，格式为当前站点 URL + `/admin/store/home`

接口：`/store/purchase`

位置：`/app/Service/Bind/App.php:474`

## 10. 插件安装 / 更新下载

用途：从官方服务器下载插件 zip 或更新 zip。

触发场景：后台应用商店点击安装插件或更新插件时调用。

字段：

- `plugin_id`
- `sign`
- HTTP Header `appId`
- HTTP Header `appKey`

接口：

- `/store/install`
- `/store/update`

下载结果会写入本地 `/kernel/Install/OS/*.zip`，随后解压到：

- `/app/Plugin/{key}/`
- `/app/Pay/{key}/`
- `/app/View/User/Theme/{key}/`

位置：`/app/Service/Bind/App.php:101`、`/app/Service/Bind/App.php:151`、`/app/Service/Bind/App.php:208`、`/app/Controller/Admin/Api/App.php:282`

## 11. 插件购买记录

用途：查询某个插件的购买 / 授权记录。

触发场景：后台应用商店查看插件购买记录时调用。

字段：

- `plugin_id`

接口：`/store/records`

位置：`/app/Service/Bind/App.php:286`、`/app/Controller/Admin/Api/App.php:427`

## 12. 解绑授权

用途：解绑某个插件授权。

触发场景：后台应用商店购买记录里点击解绑授权时调用。

字段：

- `auth_id`

接口：`/store/unbind`

位置：`/app/Service/Bind/App.php:297`、`/app/Controller/Admin/Api/App.php:435`

## 13. 绑定等级 / 服务 / 修改商店密码

用途：应用商店账户相关操作，包括绑定授权等级、获取等级列表、获取官方服务信息、修改应用商店密码。

触发场景：后台应用商店相关功能页面操作时调用。

接口：

- `/store/bindLevel`
- `/store/levels`
- `/store/service`
- `/store/editPassword`

位置：`/app/Service/Bind/App.php:556`、`/app/Service/Bind/App.php:567`、`/app/Service/Bind/App.php:577`、`/app/Service/Bind/App.php:589`

## 14. 开发者中心插件列表

用途：查询当前应用商店账号的开发者插件列表。

触发场景：进入后台“应用商店 -> 开发者中心”时调用。

字段：

- `page`
- `limit`

接口：`/developer/plugins`

位置：`/app/Service/Bind/App.php:453`、`/app/Controller/Admin/Api/App.php:322`、`/assets/admin/controller/store/developer.js:3`

## 15. 创建开发者插件

用途：在应用商店开发者中心创建插件条目。

触发场景：后台“开发者中心”点击创建插件并提交表单时调用。

字段：来自开发者创建表单，另外会读取本地图标文件内容并作为 `icon` 提交。

接口：`/developer/create`

位置：`/app/Service/Bind/App.php:465`、`/app/Controller/Admin/Api/App.php:347`

## 16. 插件源码 / 插件包上传

用途：把开发者本地 zip 包上传到官方服务器，供后续提交审核。

触发场景：后台“开发者中心”上传安装包或更新包时调用。普通用户安装别人插件不会上传本地源码。

上传内容：

- 插件安装 zip
- 插件更新 zip

接口：

- `/open/project/upload`
- `/developer/createKit`
- `/developer/createUpdate`

流程：

1. 本地先通过 `/admin/api/upload/send` 上传 zip 到本机缓存。
2. 后端读取本地 zip，用 multipart 上传到官方 `/open/project/upload`。
3. 官方返回路径后，再提交到 `/developer/createKit` 或 `/developer/createUpdate`。
4. 本地缓存 zip 随后被删除。

位置：`/app/Service/Bind/App.php:510`、`/app/Service/Bind/App.php:490`、`/app/Service/Bind/App.php:534`、`/app/Controller/Admin/Api/App.php:361`、`/app/Controller/Admin/Api/App.php:394`

## 17. 开发者插件删除 / 定价

用途：删除开发者插件、设置开发者插件价格。

触发场景：后台“开发者中心”对已创建插件进行管理操作时调用。

接口：

- `/developer/deletePlugin`
- `/developer/priceSet`

位置：`/app/Service/Bind/App.php:500`、`/app/Service/Bind/App.php:545`、`/app/Controller/Admin/Api/App.php:385`、`/app/Controller/Admin/Api/App.php:418`

## 18. 主程序更新包下载

用途：下载官方主程序更新包并执行升级流程。

触发场景：后台点击主程序更新时调用。版本列表来自 `/open/project/version` 返回的 `update_url`。

字段：

- `update_url`：官方版本接口返回的更新包地址

行为：

- 下载 `update.zip`
- 解压更新包
- 导入 `update.sql`
- 如果存在 `update.php`，会 `require` 并执行更新类的 `handle()`
- 覆盖本地程序文件

位置：`/app/Service/Bind/App.php:308`

## 19. 额外说明

正常“浏览 / 下载 / 安装插件”主要会上报：

- `appId`
- `appKey` / HWID
- `sign`
- `plugin_id`
- 插件查询条件

版本检查和后台广告即使不购买插件也可能发生：

- 版本检查会提交站点域名和客户端 IP。
- 后台仪表盘会拉取官方广告 / 公告内容。

它不会在普通安装插件时自动提交：

- 本地插件源码
- 开发者插件 zip
- 开发者图标文件

这些只在“开发者中心”发布或更新插件时才会提交。
