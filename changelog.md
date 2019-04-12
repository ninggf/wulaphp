### Version 2.8.5
**2019-04-12**

* 新增
    * RbacSupport检测用户状态,如果用户状态`status=0`调用`onLocked`方法
    * AdminController触发`mvc\admin\onLocked`勾子（用户`status=0`时）
    * 缓存测试用例
    * 认证测试用户
* 修复
    * 命名空间错误问题

### Version 2.7.0
**2019-03-20**

* 新增: wulaphp 初始化命令脚本,可以通过该脚本实现:
    1. `init` 初始化一下基于***wulaphp***的项目。
    2. `upgrade` 升级`artisan` tool。

### Version 2.6.0
**2019-03-03**

* 新增特性：UploadSupport 用于上传文件
* 新增特性：BreadCrumbSupport 用于支持面包屑导航和恢复搜索条件
* 新增类: ImageTool
* 新增类：LoopScript
* QRcode支持直接生成base64格式
* 优化built in server处理流程
* 优化Response
* 支持直接读取modules,themes里的资源
* 修复`here`修饰器的BUG
* 优化`service`命令
* 控制器方法可以将类实例当视图返回
* 添加FtpUploader用于将文件上传到FTP服务器

### Version 2.5.12
**2019-01-19**

* 增强
    * Table类的select,update,delete,insert等方法不可被重写.
    * 使用`template`函数时可以像`view`函数一样省略`.tpl`扩展名.
    * 优化错误输出页面.
    
### Version 2.5.11
**2018-12-12**

* 新增
    * Scws类
    
### Version 2.5.10
**2018-12-04**

* 新增
    * `service`命令添加`config`子命令
    * 新增两个勾子`on_load_xxx_config`与`on_load_xxx_dbconfig`

### Version 2.5.9
**2018-11-18**

* 新增
    * View::findOne方法
* 优化
    * JsonView输出
    * View::__callStatic方法
* 修复
    * MetaTable::getMeta方法
* 过时
    * 标记View::get方法过时，使用findOne。
    
### Version 2.5.8
**2018-11-14**
  
* 优化
    * Annotation::getJsonArray支持URL参数配置
    * AdminController支持loginBack注解
    
### Version 2.5.7
**2018-11-12**

* 新增
    * MetaTable用于快速操作meta表    
* 优化
    * 默认模块分发器不会分发其它模块的URL
    
### Version 2.5.6
**2018-10-23**

* 修复
    * 无法路由"/"BUG

### Version 2.5.5
**2018-10-23**

* 新增
    * App::reloadCfg用于重新加载配置。
* 修改
    * DatabaseConnection::query 与 DatabaseConnection::cud不再记录错误日志。如果记录日志请通过它的`$error`属性获取。
    
### Version 2.5.4
**2018-10-19**

* 修复:
    * App::table BUG (数据库连接失效)
* 优化:
    * 无法开启会话提示

### Version 2.5.3
**2018-10-15**

* 新增:
    * RESTFul支持
        * [RESTFulServer](https://www.wulaphp.com/guide/restful/server.html)
        * [RESTFulClient](https://www.wulaphp.com/guide/restful/client.html)
    * 文件[上传器](https://www.wulaphp.com/guide/utils/uploader)
    * [存储器](https://www.wulaphp.com/guide/utils/storage)
    
### Version 2.5.2
**2018-09-30**

* 新增:
    * 支持php build-in server
    * ExcelView
    * Module::loadFile方法用于加载模块文件内容
    * 添加存储器`Storage`
* 优化:
    * 调整语言检测逻辑
    * 固化`artisan`命令的`-h`和`--help`参数
    * `service`命令
    * 默认配置项详见[配置文档](http://www.wulaphp.com/guide/config/cfg.html)

### Version 2.5.1
**2018-09-17**
* 新增:
    * 添加module管理子命令
* 修复:
    * 修复queryOne BUG


### Version 2.5.0
**2018-09-14**
* 兼容性调整
    * 向下兼容到PHP5.6.9
* 新功能:
    * App::run()方法添加参数
    * 配置继承加载机制
    * 添加`pview`函数
    * 数据库SQlite支持
    * 添加`admin`子命令 (`#php artisan admin --help`了解更多)
* 优化:
    * 引导过程
    * 优化响应缓冲区处理
    * 添加travis支持
    * 优化路由器
        * 子模块功能
        * URL别名机制
    * artisan命令工具

### Version 2.4.0
**2018-09-05**
* 修复
    * 修复App::db不能接收array参数错误

* 增强
    * DatabaseConnection::trans方法将抛出所有实现了IThrowable接口的异常
* 新增
    * 添加IThrowable接口