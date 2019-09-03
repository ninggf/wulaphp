<p align="center"><img src="https://d33wubrfki0l68.cloudfront.net/images/1d83c09b2f0cd8231a54f2a8a6eaee9754b802fb/logo.png" width="140" height="140"></p>
<p align="center">
<a href="https://travis-ci.org/ninggf/wulaphp"><img src="https://travis-ci.org/ninggf/wulaphp.svg?branch=v3.0.1-dev" alt="Build Status"></a>
<a href="https://packagist.org/packages/wula/wulaphp"><img src="https://poser.pugx.org/wula/wulaphp/d/total.svg" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/wula/wulaphp"><img src="https://poser.pugx.org/wula/wulaphp/v/stable.svg" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/wula/wulaphp"><img src="https://poser.pugx.org/wula/wulaphp/license.svg" alt="License"></a>
</p>


# wulaphp

它的名来自《异星战场》里那个速度极快的火星狗狗，可以用它开发`WEB`应用和`命令行`应用。它确实很快：学习快、开发快、运行快！

# 依赖

```json
{
    "require": {
        "php": ">= 7.1",
        "ext-json": "*",
        "ext-pcre": "*",
        "ext-PDO": "*",
        "ext-pdo_mysql": "*",
        "ext-mbstring": "*",
        "ext-curl": "*",
        "ext-Reflection": "*",
        "ext-SPL": "*",
        "ext-zip": "*",
        "smarty/smarty": "^3.1",
        "psr/log": "^1.0",
        "wula/common": "^1.0"
    },
    "require-dev": {
        "phpunit/phpunit": "^7.5",
        "phpoffice/phpspreadsheet": "^1.4"
    },
    "suggest": {
        "ext-redis": "*",
        "ext-memcached": "*",
        "ext-apcu": "*",
        "ext-sockets": "*",
        "ext-posix": "*",
        "ext-pcntl": "*"
    }
}
```

> wulaphp `v2.10.x`版本可以运行在`PHP 5.6.9`及更高的版本上。

# 安装

## Composer 方式

我们推荐通过`Composer`方式安装`wulaphp`。

### 安装

`# composer require wula/wulaphp -vvvv`

> 国内的小朋友请耐心的等待或多执行几次上边的代码。如果还是安装不成功，请通过[下载](#下载安装)的方式进行安装。

### 初始化

安装命令完成后，执行以下代码进行项目初始化工作:

`# php vendor/bin/wulaphp init`

如果你运行在`类Unix`系统上，还需要执行以下操作将目录变为可读写：

`# chmod 777 storage storage/tmp storage/logs`

## 下载安装

按以下步骤下载并解压到相应目录即可完成安装:

### Windows 系统

1. 点击此处[下载](https://www.wulaphp.com/wulaphp-latest.zip)最新版本的wulaphp。
2. 解压到相应的目录即可。

### 类 Unix 系统

`# wget https://www.wulaphp.com/wulaphp-latest.tar.gz`

`# tar -zxf wulaphp-latest.tar.gz`

`# cd wulaphp-latest`

`# chmod 777 storage storage/tmp storage/logs`

> 如果你能正常访问`composer`，建议执行一下`#composer update -vvv`将所有依赖包升级到最新版本。

## 验证

打开命令行，进入应用根目录(<small>artisan脚本所在的目录</small>)并执行下边的命令(<small>使用内建服务器运行wulaphp</small>)：

`php -S 127.0.0.1:8090 -t wwwroot/ wwwroot/index.php`

通过浏览器访问<a href="http://127.0.0.1:8090" target="_blank">http://127.0.0.1:8090</a>，看到下边的输出:

**Hello wula !!**

恭喜你，安装完成。

> 如果未能看到上边的输出，请移步[FQA](https://www.wulaphp.com/fqa.html#install)

# 文档

1. [wiki](https://github.com/ninggf/wulaphp/wiki)
2. [文档](https://www.wulaphp.com/)

# License

[MIT](https://github.com/ninggf/wulaphp/blob/v2.0/LICENSE) License.
