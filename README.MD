<p align="center">
  <a href="https://faka.wiki/">
    <img src="https://raw.githubusercontent.com/lizhipay/acg-faka/refs/heads/main/favicon.ico" width="120" height="120" style="border-radius: 20px;" alt="ACG-FAKA-LOCAL系统">
  </a>
</p>

<br>
<p align="center">
<span>
<img src="https://faka.wiki/svg/php.svg" alt="php8.0,8.1">
</span>
<span>
<img src="https://faka.wiki/svg/mysql-version.svg" alt="mysql5.6+">
</span>
<span><img src="https://faka.wiki/svg/license.svg" alt="license"></span>
</p>

## 前提概要
> 本分支程序严格遵守ACG-FAKA系统法律许可，基于 MIT 协议开源，并且完全免费。该程序的初衷是为开发者提供学习和研究的机会。未取得合法资质，严禁将本程序用于任何商业用途，尤其是禁止利用本程序搭建平台进行商品销售。
>
> 为什么我们会启动这个项目？详细请看该文档[原因与问题报告](https://github.com/NoDoorAction/Acg-Faka-Local/blob/main/Problem.md)
>
> 用户在使用或学习本程序时，必须严格遵守法律法规。我们提倡依法行事，尊重法律，坚守法律，避免对社会产生不良影响。
>
> 使用本程序即表示您已充分理解并同意本分支及原始版本法律声明的所有内容。

## 快速体验
- 前台演示：[https://acg.nodooraction.org/](https://acg.nodooraction.org/)
- 文档地址：[开发规范](https://github.com/NoDoorAction/Acg-Faka-Local/blob/main/Problem.md#%E5%BC%80%E5%8F%91%E8%A7%84%E8%8C%83)

## 功能简介
- 在严格基于**ACG-FAKA**的原始代码上，我们仅对其上报，联网部分进行了移除，**对其商业闭源代码在基于MIT许可证的前提下进行了反混淆**，以下是原始功能。
- 支付系统，拥有强悍的插件扩展能力，现目今已经支持全网任意平台，任意支付渠道。
- 云更新，如果系统升级新版本，你无需进行繁琐操作，只需要在你的店铺后台就可以无缝完成升级。
- 商品销售，支持商品配图、会员价、游客价、邮件通知、卡密预选（用户可以预选自己想购买的那个账号或者卡号）、API对接、强制登录购买、强悍的自定义控件功能、限时秒杀、批发优惠、优惠卷、等众多功能。
- 分站系统，前台用户可以开通分站，分站可以独立运行，也可以卖主站商品，有点类似商业店铺了。
- 会员系统，会员/商户融为一体，支持会员等级，以及商户等级完全自定义，以及商品可自定义会员等级对应价格。
- 推广/代理系统，拥有三级分销返佣功能，注册账号即实现自动发展下级。
- 共享店铺系统，可以在后台直接对接别人的店铺，通过扣除余额来进行无感知进货。
- 应用商店，拥有众多插件以及模板，让你的店铺变得格外强大。
- 界面美观，完美支持PC和手机，真正的内外二次元文化。
- 强悍的扩展能力，你可以通过本程序在几分钟之内快速的实现你任意想实现的在线购物功能，例子如下： 
  - 游戏方面，物品购买即时到玩家背包
  - 商业软件余额充值
  - 商业软件自动授权
  - 论坛/社区VIP自动开通
  - 只要你想得到，没有做不到。
- 还有更多强大的功能，需要安装自己发掘。至此，介绍完毕。

## 安装教程

- 在安装之前，请检查你的系统环境，`php>=8.0`，`MySQL版本>=5.6[不推荐5.6后续升级可能会有问题，推荐5.7或者8.0]`，因为使用了大量的PHP8注解以及PHP8的新特性，所以php版本不得不从8.0起，这里还需要注意。
- 在(发布页)[https://github.com/NoDoorAction/Acg-Faka-Local/releases/]内,overlay.zip为覆盖包，如果您需要安装则下载下方Source code压缩包到您的服务器，并进行解压，设置运行目录。
- 以上步骤完成后，然后配置伪静态，Apache无需配置，根目录已经有.htaccess文件了，但如果你是Nginx，则需要配置伪静态。
- 下面是Nginx伪静态规则：
```
location / {
      if (!-e $request_filename){
              rewrite ^(.*)$ /index.php?s=$1 last; break;
      }
}
```
- Windows IIS服务器环境，可以使用下面伪静态规则：
```
<rules>
	<rule name="acg_rewrite" stopProcessing="true">
		<match url="^(.*)$"/>
		<conditions logicalGrouping="MatchAll">
			<add input="{HTTP_HOST}" pattern="^(.*)$"/>
			<add input="{REQUEST_FILENAME}" matchType="IsFile" negate="true"/>
			<add input="{REQUEST_FILENAME}" matchType="IsDirectory" negate="true"/>
		</conditions>
		<action type="Rewrite" url="index.php?s={R:1}"/>
	</rule>
</rules>
```
- 配置完成后，访问你的首页，即可开始安装
- 安装完成后，后台地址是：`https://你的域名/admin`

## 更多支持
- 暂无，如果您发现了任何问题请在issue中提出，开发者会及时回复。
