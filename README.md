<p align="center"><img src="https://d33wubrfki0l68.cloudfront.net/images/1d83c09b2f0cd8231a54f2a8a6eaee9754b802fb/logo.png" width="140" height="140"></p>
<p align="center">
<a href="https://travis-ci.org/ninggf/wulaphp"><img src="https://travis-ci.org/ninggf/wulaphp.svg?branch=v2.0" alt="Build Status"></a>
<a href="https://packagist.org/packages/wula/wulaphp"><img src="https://poser.pugx.org/wula/wulaphp/d/total.svg" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/wula/wulaphp"><img src="https://poser.pugx.org/wula/wulaphp/v/stable.svg" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/wula/wulaphp"><img src="https://poser.pugx.org/wula/wulaphp/license.svg" alt="License"></a>
</p>

# 关于wulaphp

一个小巧、高效、灵活的`PHP MVC`框架。她的名来自《异星战场》里那个速度极快的狗狗，她真的很快，是的`wulaphp`和这条狗狗一样－－`快`！

**她:**

- 小巧，她是一个简单的基于MVC设计模式开发的框架。
    - 她是一个composer包，可以通过composer进行引用
    - 她只依赖`psr/log`与`smarty/smarty`这两个第三方库
- 基于插件(plugin)机制提供无限扩展性.
- 利用模块(module)来合理组织你的应用.
- 提供扩展(extension)机制,将通用功能高度内聚。
- 允许自定义View模板，用你最熟悉的模板，一切都是那么亲切.
    - 内置Smarty,Xml,Json,PHP等
- 支持Annotation（注解）让编码不那么死板。
    - 权限控制
    - 布局配置
    - 其它数据...
- 基于Trait为控制器(Controller)提供自定义特性,
    - `SessionSupport`: Session支持
    - `PassportSupport`: 通行证支持,依赖`SessionSupport`
    - `RbacSupport`: Rbac权限支持,依赖`PassportSupport`
    - `CacheSupport`: 缓存支持
    - `LayoutSupport`: 布局支持(仅限Smarty模板)
    - ...更多自定义特性
- 适度封装了数据库访问(Table,View)与简易的ORM.
    - 集成验证特性
    - 事务处理透明
- 支持所见即所得的URL路由及基于插件的的URL路由自定义功能.
    - 支持子模块
    - 支持默认模块
    - 支持路由表
    - 支持URL别名
    - 支持**自定义的路由器**
- 支持多语言
    - 可以根据语言自动选择模板
- 基于`apc`,`yac`,`xcache`提供运行时缓存，让应用在线上模式下飞起来.
- 基于redis提供分布式部署支持.
    - 内置基于`Redis`的分布式锁
- 基于redis或memcached提供缓存支持.
    - 可通过插件来自定义不同的缓存支持
- 提供了`artisan`工具,告别手工脚本
    - `service` 命令,让你的脚本在后台运行, 支持三种类型:
        * `cron` 精确到秒的定时任务
        * `script`或`parallel` 同时运行多个脚本
        * `gearman` Gearman Worker 
    - `run` 同时运行多个脚本
    - `cron` 精确到秒的定时任务
    - 你自己实现的命令

是的我们又造了一个飞快的轮子！！

欢迎加入我们的QQ群: **371487281**一起讨论讨论.

# 安装运行

通过`Composer`命令,只需要简单的`composer create-project wula/wula`即可享用wulaphp。更多安装方法请参见[安装文档](http://www.wulaphp.com/guide/installation.html).

> 说明:
>
> [wula](https://packagist.org/packages/wula/wula)是wulaphp框架的炮架子，它为wulaphp框架提供项目的基础目录结构,

# 相关资料

* [中文文档](http://www.wulaphp.com/guide/)
* [中文手册](http://www.wulaphp.com/manual/)
* [常见问题](http://www.wulaphp.com/faq/)

# wulacms

[wulacms](https://packagist.org/packages/wula/wulacms)是一个基于wulaphp开发的微内核内容管理框架,提供:
* 内置模块:
    * 内核模块[wulacms/system](https://packagist.org/packages/wulacms/system) - 账户管理,模块管理,权限管理,日志管理,任务管理等功能...
    * 后台界面[wulacms/backend](https://packagist.org/packages/wulacms/backend) - 基于layui的管理界面
    * cms模块[wulacms/cms](https://packagist.org/packages/wulacms/cms) - 多模型,版本控制,多站点,主题支持...
    * RESTFull[wulacms/rest](https://packagist.org/packages/wulacms/rest) - RESTFull风格API调用与接入管理.
    * 多媒体[wulacms/media](https://packagist.org/packages/wulacms/media) - 附件管理
* 可选模块:
    * 有很多可选模块供君选择,请[看这儿](https://packagist.org/packages/wulacms).
* 特性:
    * 多级缓存
    * 防CC
    * 防缓存雪崩
    * 分布式部署

> 如果你不想从头开始一个项目,建议基于`wulacms`进行两次开发!

# 贡献(Contribution)

我们感谢并欢迎你为wulphp所做的贡献:

* 将BUG或建议请提交到[issues](https://github.com/ninggf/wulaphp/issues).
* 通过`Pull Request`提交BUG FIX或新的特性.
* 为wulaphp撰写[文档](https://github.com/ninggf/wulaphp.com)

> `Pull Request`注意事项:
> 1. 推荐使用[PhpStorm](https://www.jetbrains.com/phpstorm/)IDE.
> 2. 为了减少代码合并时产生的冲突,需要您的代码风格与我们保持一致:
>       * 下载适合PhpStorm使用的[代码风格配置](https://raw.githubusercontent.com/ninggf/wulaphp/v2.0/ide-code-style.xml)文件.
>       * 导入到PhpStorm中.

# (许可)License

[MIT](https://github.com/ninggf/wulaphp/blob/v2.0/LICENSE)