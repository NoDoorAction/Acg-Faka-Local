<p align="center">
  <a href="https://faka.wiki/">
    <img src="https://raw.githubusercontent.com/lizhipay/acg-faka/refs/heads/main/favicon.ico" width="120" height="120" style="border-radius: 20px;" alt="异次元店铺系统">
  </a>
</p>

# Acg-Faka-Local

> 截止至目前，我们了解到ACGFAKA在大部分插件收费的情况下，最终决定由其官方使用ChatGPT扫描使用者站点内容，在其本身收集服务器相关特征信息的情况下我们认为ACGFAKA官方的行为超过了必要限度，本着开源开放友好互助的原则，我们启动了这个项目并对其商业闭源代码进行了反混淆，由于本团队编者能力有限，如果在使用过程中出现任何问题还望海涵，造成任何法律后果应该由使用者本身自行承担。

**ACG-FAKA-LOCAL** 是 *ACK-FAKA* 一个线上发卡平台 的反混淆源码的本地版本，几乎是由 [Claude](https://claude.com/)协助逆向得到。目标版本为 **3.4.9Latest**。

原始应用商城所属的 `Plugin.php` 经过完整混淆：压缩壳 + 自定义 PHP VM 虚拟化 + 字节码解释执行 + AES 字符串加密 + 动态 `eval` / `include` / 反射调用桥接 + 控制流隐藏。反编译者使用 Opus 4.7 对其进行了反混淆，并结合 ChatGPT 和 Sonnet 4.6 对其 变量 / 字段 命名进行推断，最后将其还原为可读的 PHP 源代码。最终产物是一个可以直接在 PHP 中运行并构建的工程，而不是混淆器生成的压缩 VM bytecode blob。

> ⚠️ 本仓库**仅供学习与研究目的发布** —— 基于(https://github.com/lizhipay/acg-faka) MIT License，一份由互联网爱好者学习研究而产生的产物，您应该遵守其使用协定，如产生任何法律后果责任自负⚠️

## 许可

原始混淆字节码使用MIT许可证。混淆PHP源码是项目方自己写的代码，他们故意混淆后放进来这种情况下，理论上它也属于 MIT 项目的一部分，MIT 允许"modify"，反混淆可以视为一种修改形式。而在反混淆后我们向您展示的是这个项目可能的最原始的状态，对于仓库的中的内容**仅供研究与学习使用**。如果你是 ACG-FAKA 的原作者并希望本仓库下架或重新授权，请提 Issues。虽然提了也不会搭理你。

## 原始 仓库 + 许可证部分

[原始仓库](https://github.com/lizhipay/acg-faka)
[MIT许可证](https://github.com/lizhipay/acg-faka?tab=MIT-1-ov-file)

需要说明的是，我们对这个项目被外挂，黑灰产业滥用表示深感遗憾，我们同情并理解你们的遭遇。但是在源码中过度收集信息这与我们的观念不符。

## 细节

经过 Opus 4.7 长达 9178 秒的分析，Opus 认为所有函数和变量名都被改造成了 Z5Encrypt 风格的 PHP VM 混淆形式。除了顶层压缩封装外，还叠加了 PHP VM 保护、反射调用桥接和控制流隐藏的组合混淆。
Opus 随便看了 91 秒就把反混淆器写出来了。被 Rename 以后代码确实已经烂到不适合人类直接阅读，因此我们接入了Sonnect 4.6 以较低的Tokens成本进行了78秒的修修补补后完成了这一份**开源但是如开源**的源代码反混淆

## 反混淆分析产物

反混淆分析文件放在 [`deobf`](deobf/) 目录中，可以直接点击进入查看。

| 文件 | 用途 |
| --- | --- |
| [`deobf-log.md`](deobf/deobf-log.md) | 反混淆过程记录，说明 `Plugin.php` 的壳结构、VM runtime、bytecode 解析方式、分析约束和最终结论。 |
| [`Plugin.php`](deobf/Plugin.php) | 原始混淆样本，用于和还原结果对照，不建议直接执行。 |
| [`Plugin_deobf.php`](deobf/Plugin_deobf.php) | 根据 VM 行为还原出的可读 PHP 版本，用于理解 `_plugin_*` 函数真实逻辑。 |
| [`plugin_vm_bytecode.md`](deobf/plugin_vm_bytecode.md) | VM 字节码的人类可读反汇编结果，按函数列出 opcode、寄存器、跳转和操作数预览。 |
| [`plugin_vm_bytecode.json`](deobf/plugin_vm_bytecode.json) | VM 字节码的结构化 JSON 导出，保留更完整的指令、操作数和字节信息，适合继续写脚本分析。 |

## 后续计划

后续会对此项目进行完整离线化，去除后台广告拉取逻辑，并允许普通用户自行编写插件、上传插件并在本地直接使用。

## 没有发现后门

经过我们的分析，发现这一份开源代码中**不完全保证不**含有任何的恶意执行代码与可能存在的提权漏洞，并且笔者承认ACG-FAKA开发团队对于反SQL/JS/XSS注入等攻击行为的做出努力。
但是在其源代码中我们发现了站点域名和 IP 上报逻辑：

```php
public function getVersions(): array
{
    if (Context::get(Base::LOCK) == "") {
        file_put_contents(BASE_PATH . "/kernel/Install/Lock", Str::generateRandStr(32));
    }

    return (array)$this->post("/open/project/version", [
        "key" => "faka",
        "domain" => \App\Util\Client::getDomain(),
        "client_ip" => \App\Util\Client::getAddress()
    ]);
}
```

影响：

- 商店服务器可以知道你的站点域名，并拿到当前客户端 IP。
- 在官方已经宣称会使用 ChatGPT 扫描使用者站点内容的前提下，这套逻辑还会自动获取网站域名、服务器 IP 并提交，服务器相关特征信息自然也就进入了上游视野。
- 如果站点本来不想暴露给上游，这属于可追踪信息；至于后续是否自动关联应用商店账号、是否自动封禁账号、是否“无需人工干预，违规无所遁形”，那就只能相信异次元官方的自动化能力确实足够先进了。

代码在 `app/Service/Bind/App.php` (line 132)。
另外还有拉取广告逻辑：

```php
public function ad(): array
{
    return (array)$this->post("/open/project/ad", ["key" => "faka"]);
}
```

后台接口会直接调用这个方法：

```php
public function ad(): array
{
    return $this->json(200, "ok", $this->app->ad());
}
```

进入后台仪表盘时，前端会请求这个接口并把返回内容渲染到页面：

```javascript
function loadAd() {
    const $adHandle = $('.ad-html');
    // 加载公告数据
    $.get("/admin/api/app/ad", res => {
        if (res.code != 200) {
            $adHandle.html('<div class="text-center text-muted py-4">暂无公告</div>');
            return;
        }

        if (res.data.length === 0) {
            $adHandle.html('<div class="text-center text-muted py-4">暂无公告</div>');
            return;
        }

        let html = "";
        res.data.forEach(item => {
            html += _AD_HTML.replace("[title]", item.title)
                .replace("[create_time]", item.create_date)
                .replace("[url]", item.url ? item.url : "javascript:void(0)")
                .replace("[target]", item.url ? 'target="_blank"' : '');
        });
        $adHandle.html(html);
    });
}
```

影响：

- 用户进入后台仪表盘时就会向官方商店请求广告内容，不是用户主动打开应用商店后才发生。
- 广告标题、时间、链接来自官方接口返回值，并被拼入后台页面；也就是说，后台管理页并不是一个纯粹的本地管理界面，而是顺手给异次元官方留了一个广告展示位，不符合开源项目的理念。
- 对站长来说，这种“一进后台先看广告”的体验确实足够狗屎。

代码在 `app/Service/Bind/App.php`、`app/Controller/Admin/Api/App.php`、`assets/admin/controller/dashboard/index.js`。

开发者中心相关接口如下，可以看到插件并不是普通用户写完后就能直接独立使用，而是被设计成需要走应用商店开发者流程：

```php
public function developerPlugins(array $data): array
{
    return $this->storeRequest("/developer/plugins", $data);
}

public function developerCreatePlugin(array $data): array
{
    return $this->storeRequest("/developer/create", $data);
}

public function developerCreateKit(array $data): array
{
    return $this->storeRequest("/developer/createKit", $data);
}

public function developerUpdatePlugin(array $data): array
{
    return $this->storeRequest("/developer/createUpdate", $data);
}

public function developerPluginPriceSet(array $data): array
{
    return $this->storeRequest("/developer/priceSet", $data);
}
```

开发者提交插件安装包和更新包时，会先把本地 zip 上传到官方接口，再把返回的路径提交到开发者接口。也就是说，正常路径不是“我写了一个插件，放到本地就能完全独立使用”，而是“我写了一个插件，先上传给异次元官方，然后等待异次元官方审核”：

```php
public function developerCreateKit(): array
{
    $file = $_POST['resource'];
    if (!file_exists(BASE_PATH . $file)) {
        throw new JSONException("请重新上传插件包");
    }
    //上传安装包
    $upload = $this->app->upload([
        [
            'name' => 'file',
            'contents' => fopen(BASE_PATH . $file, 'r'),
            'filename' => 'file.zip'
        ]
    ]);
    //删除本地安装包
    unlink(BASE_PATH . $file);
    //需要审核的安装包临时存放地址
    $_POST['resource'] = $upload['path'];
    return $this->json(200, "提交成功", $this->app->developerCreateKit($_POST));
}

public function developerUpdatePlugin(): array
{
    $file = $_POST['audit_resource'];
    if (!file_exists(BASE_PATH . $file)) {
        throw new JSONException("请重新上传插件包");
    }
    //上传更新包
    $upload = $this->app->upload([
        [
            'name' => 'file',
            'contents' => fopen(BASE_PATH . $file, 'r'),
            'filename' => 'file.zip'
        ]
    ]);
    //删除本地更新包
    unlink(BASE_PATH . $file);
    //需要审核的安装包临时存放地址
    $_POST['audit_resource'] = $upload['path'];
    return $this->json(200, "提交成功", $this->app->developerUpdatePlugin($_POST));
}
```

前端开发者中心只在接口可用时渲染，否则会跳回插件市场。异次元官方通过开发者中心接口把插件创建、安装包上传、更新包上传、定价都收束到应用商店流程里：

```javascript
const table = new Table("/admin/api/app/developerPlugins", "#dev-plugin-table");

$('.developerCreatePlugin').click(() => {
    _Modal();
});

error: () => {
    window.location.href = "/admin/store/home";
}
```

影响：

- 普通用户不能按正常官方流程直接编写插件并独立使用，插件创建、更新、定价、素材提交都被放在开发者中心流程里。
- 本地 zip 包会先上传到官方接口，再由开发者接口提交审核路径，插件分发权被绑定在应用商店和开发者审核流程上。
- 这套流程的实际体验就是：你写插件不算完，传上去也不算完，还需要接受异次元官方开发者审核；至于审核步骤是否繁琐、审核速度是否感人，就只能祝提交者拥有足够的耐心。

代码在 `app/Service/Bind/App.php`、`app/Controller/Admin/Api/App.php`、`assets/admin/controller/store/developer.js`。

## 状态与注意事项

- 这是**尽力而为的反混淆结果**，部分符号是根据上下文重建的，可能与原作者的命名意图不一致。

## 构建与修改

如你所见这是一个PHP 8 原生项目 + 自研 MVC/路由内核 + Composer 组件所构成的项目，如果您需要修改并二次编译本项目，您需要满足ACG-FAKA所需要的一切前置内容。

## 常见问题

Q:异次元中含有后门吗？

A:经过我们的分析，我们认为其在应用商店处收集用户相关特征信息是合理的，收集用户部署项目时对应容器的IP也是合理的。不存在任何真正意义上的能够被开发者利用的后门。

Q:如果我不使用应用商店我的容器信息会被上传吗？

A:以ACG—FAKA的开发者视角来看，他们说是离线版本，但是在对项目进行分析的时候我们发现在安装的时候已经对您的容器对应的IP进行了上传，其用意我们无从得知，但愿是为了监管。

Q:我的商店流水，订单信息会被上传吗？

A:经过Opus长达2分钟的对于本仓库反混淆后的源代码的分析，我们很遗憾的发现 app/Service/Bind 负责对外请求，app/Controller/Admin/Api 决定哪些数据发往商店

会上传服务器域名/IP：是，版本检查时上传到官方应用商店。
会上传用户商店完整流水：未发现自动上传到官方应用商店。
会上传订单发货数据：是，但主要发生在“店铺共享/远程代发货”功能中，目标是管理员配置的远程店铺。
会上传插件/授权/账号信息：是，应用商店登录、购买、安装、开发者插件上传都会发生。
仅有这些行为包含在内。
也就是说会上传*本站域名(HTTP_Host去端口)*，*本站出口IP/客户端IP*，*HWID(具体收集内容请看上文)*，*一个新站点被部署*的事件，服务端可能记录具体安装时间。

Q:我使用本版本，并且使用了应用商店，ACK-FAKA官方会知道吗？

A:当前版本对应的HWID已固化并且你登录了账号，如果他们开发团队小心眼且喜欢视奸，您的账号*可能遭遇不测*

Q:为什么你们会考虑创建本仓库？

A:素材收集于网络，部分说明收集于原始文档中，笔者只是进行了整理。在看到ACK-FAKA官方频道发布的那则公告，或许有特异人士决定捍卫自己的隐私权？笔者无从得知。

Q:如果ACG-FAKA在后续版本中更新了混淆，并且对新的被挖掘出来的漏洞进行了修复，本仓库还会更新吗？

A:您应该对您自己的行为所产生的安全风险以及导致产生的后果负责。仓库怎么办，只有天知道。

## 致谢

- 原始混淆项目：**ACK-FAKA**。
- 反混淆、符号还原与工程脚手架：**Claude** 在人工监督下完成。
- 重命名函数，恢复可读性：**Claude Sonnect**自主完成
- 仓库源代码分析，敏感信息审计：**ChatGPT**自主完成
- 仓库源码素材来源于网络，感谢对支持开源项目一定要开源的反混淆特异人士，**这位面善又友善的朋友**
