<p align="center"><img src="https://d33wubrfki0l68.cloudfront.net/images/1d83c09b2f0cd8231a54f2a8a6eaee9754b802fb/logo.png" width="140" height="140"></p>
<p align="center">
<a href="https://travis-ci.org/ninggf/wulaphp"><img src="https://travis-ci.org/ninggf/wulaphp.svg?branch=v2.0" alt="Build Status"></a>
<a href="https://packagist.org/packages/wula/wulaphp"><img src="https://poser.pugx.org/wula/wulaphp/d/total.svg" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/wula/wulaphp"><img src="https://poser.pugx.org/wula/wulaphp/v/stable.svg" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/wula/wulaphp"><img src="https://poser.pugx.org/wula/wulaphp/license.svg" alt="License"></a>
</p>


假设您：

1. php的基础知识非常扎实。
    * 面向对象知识必须扎实，不扎实的请绕道。
    * 深刻理解"低耦合，高内聚"的涵义，不然请绕道。
2. 可以熟练使用`composer`。
3. 可以熟练配置`nginx`或`apache`

如果以上假设不成立，请不要浪费您宝贵的时间，立即停止阅读以下内容并[离开](https://github.com)。

如果以上假设成立，请让`wulaphp`带你飞！

# wulaphp

1. 她的名来自《异星战场》里那个速度极快的狗狗，`wulaphp`和这条狗狗一样－－`快`！
2. 除了***快***，她还很***复杂***！
3. 因为***复杂***，所以她很***难***！
4. 因为很***难***，所以您可以选择[离开](https://github.com)。

# 依赖

```json
{
    "require": {
        "php": ">= 5.6.9",
        "ext-json": "*",
        "ext-pcre": "*",
        "ext-PDO": "*",
        "ext-pdo_mysql": "*",
        "ext-mbstring": "*",
        "ext-curl": "*",
        "ext-Reflection": "*",
        "ext-SPL": "*",
        "ext-zip": "*"
    },
    "require-dev": {
        "phpunit/phpunit": "^5.7",
        "phpunit/phpunit-mock-objects": "^3.0",
        "phpoffice/phpspreadsheet": "^1.4"
    },
    "suggest": {
        "ext-openssl": "*",
        "ext-mysqlnd": "*",
        "ext-redis": "*",
        "ext-libxml": "*",
        "ext-xml": "*",
        "ext-sockets": "*",
        "ext-posix": "*",
        "ext-pcntl": "*"
    }
}
```

# 安装

## 安装
 
1. `composer require wula/wulaphp` # 耐心一点
2. `vendor/bin/wulaphp init`

## 验证

运行`./artisan`:

```
artisan tool for wulaphp

Usage: #php artisan <command> [options] [args]

Options:
  -h, --help     display this help message
  -v             display wulaphp version

Commands:
  admin          administrate tool for wulaphp
  service        run services in background

Run  '#php artisan help <command>' for more information on a command.
```

# 运行

## Development Server

`php -S 127.0.0.1:9090 -t wwwroot/ wwwroot/index.php`

## Nginx

运行`vendor/bin/wulaphp conf nginx` 获取`nginx`配置.

## Apache Httpd

运行`vendor/bin/wulaphp conf apache` 获取`Httpd`配置.

# 文档

1. [wiki](https://github.com/ninggf/wulaphp/wiki)
2. [文档](https://www.wulaphp.com)

# License

[MIT](https://github.com/ninggf/wulaphp/blob/v2.0/LICENSE) License.
