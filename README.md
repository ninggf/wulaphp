<p align="center"><img src="https://d33wubrfki0l68.cloudfront.net/images/1d83c09b2f0cd8231a54f2a8a6eaee9754b802fb/logo.png" width="140" height="140"></p>
<p align="center">
<a href="https://travis-ci.org/ninggf/wulaphp"><img src="https://travis-ci.org/ninggf/wulaphp.svg?branch=v2.0" alt="Build Status"></a>
<a href="https://packagist.org/packages/wula/wulaphp"><img src="https://poser.pugx.org/wula/wulaphp/d/total.svg" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/wula/wulaphp"><img src="https://poser.pugx.org/wula/wulaphp/v/stable.svg" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/wula/wulaphp"><img src="https://poser.pugx.org/wula/wulaphp/license.svg" alt="License"></a>
</p>


***以下内容假设你了解`composer`，了解`nginx`或`apache`或`Development Server`。***

> 如果以上假设不成立，请不要浪费您宝贵的时间，立即停止阅读以下内容并离开。

# wulaphp

1. 她的名来自《异星战场》里那个速度极快的狗狗，`wulaphp`和这条狗狗一样－－`快`！
2. 除了***快***，她还很***复杂***！
3. 因为***复杂***，所以她很***难***！
4. 不推荐在`windows`操作系统运行她！

# 安装

1. `composer require wula/wulaphp`
2. `vendor/bin/wulaphp init`

# 运行

## Development Server

`php -S 127.0.0.1:9090 -t wwwroot/ wwwroot/index.php`

## Nginx

运行`vendor/bin/wulaphp conf nginx` 获取`nginx`配置.

## Apache Httpd

运行`vendor/bin/wulaphp conf apache` 获取`Httpd`配置.

# 文档

[wiki](https://github.com/ninggf/wulaphp/wiki)

# License

[MIT](https://github.com/ninggf/wulaphp/blob/v2.0/LICENSE) License.
