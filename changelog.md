### Version 2.5.1
**2018-09-10**
* 新功能:
    * App::run()方法支持`$url`和`$method`参数
 
* 优化:
    * 引导过程
    * 优化响应缓冲区处理
    * 添加travis支持
    * 向下兼容性调整

### Version 2.5.0
**2018-09-10**
* 修复
    * 向下兼容到PHP5.6.9

### Version 2.4.0
**2018-09-05**
* 修复
    * 修复App::db不能接收array参数错误

* 增强
    * DatabaseConnection::trans方法将抛出所有实现了IThrowable接口的异常
* 新增
    * 添加IThrowable接口