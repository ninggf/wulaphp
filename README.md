<p align="center"><img src="https://d33wubrfki0l68.cloudfront.net/images/1d83c09b2f0cd8231a54f2a8a6eaee9754b802fb/logo.png" width="140" height="140"></p>
<p align="center">
<a href="https://travis-ci.org/ninggf/wulaphp"><img src="https://travis-ci.org/ninggf/wulaphp.svg?branch=v2.0" alt="Build Status"></a>
<a href="https://packagist.org/packages/wula/wulaphp"><img src="https://poser.pugx.org/wula/wulaphp/d/total.svg" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/wula/wulaphp"><img src="https://poser.pugx.org/wula/wulaphp/v/stable.svg" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/wula/wulaphp"><img src="https://poser.pugx.org/wula/wulaphp/license.svg" alt="License"></a>
</p>

# wulaphp

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
    - 可以根据语言自动选择模板哦
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
- 运维友好

是的我们又造了一个飞快的轮子！！

欢迎加入我们的QQ群: **371487281**一起讨论讨论，

发现任何BUG或建议请提交到[issues](https://github.com/ninggf/wulaphp/issues).


传送至文档[立即开始](https://github.com/ninggf/wulaphp/wiki).