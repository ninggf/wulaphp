### Version 2.5.0
**2018-09-13**
* 新功能:
    * App::run()方法支持`$url`和`$method`参数
    * 配置继承加载机制
* 优化:
    * 引导过程
    * 优化响应缓冲区处理
    * 添加travis支持
    * 向下兼容到PHP5.6.9

### Version 2.4.0
**2018-09-05**
* 修复
    * 修复App::db不能接收array参数错误

* 增强
    * DatabaseConnection::trans方法将抛出所有实现了IThrowable接口的异常
* 新增
    * 添加IThrowable接口