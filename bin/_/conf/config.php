<?php
/**
 * 应用配置文件.
 */
return [
    # 调试模式： debug,warn,info,error
    'debug' => env('app.logger.level', 'debug'),
    # 静态资源URL根路径，影响以下函数和模板修饰器
    # 函数: App::assets、App::res、App::src、App::vendor
    # 修饰器：assets、res、base、vendor
    #'static_base' => env('app.static.base', '/'),
    # CDN资源URL根路径，影响以下函数和模板修饰器
    # 函数：App::cdn
    # 修饰器：cdn
    #'cdn_base'    => env('app.cdn.base', '/'),
    # 预加载模块,指定需要预加载的模块id
    #'modules'     => [],
    # 模块域名配置 key=>value（模块id=>host)
    #'domains'=>[],
    # CORS配置
    #'cors'  => [
    #    'Access-Control-Allow-Origin'  => '*',
    #    'Access-Control-Allow-Methods' => 'GET,HEAD,POST',
    #    'Access-Control-Allow-Headers' => '*',
    #    'Access-Control-Max-Age'       => '1800',
    #    'Access-Control-Allow-Credentials'=>false
    #],
    # 离线模式
    #'offline'     => false,
    # 离线时允许的访问的IP
    #'allowedIps'  => '',
    # 离线提示消息
    #'offlineMsg'  => 'Service Unavailable',
    # Cookie配置
    #'cookie'      => [
    #    'expire'   => 0,
    #    'path'     => '/',
    #    'domain'   => '',
    #    'security' => false,
    #    'httponly' => false,
    #    'SameSite' => 'None'
    #],
    # Session 过期配置
    #'expire' => 0,
    # 文件上传配置
    #'upload'      => [
    #    'dir'                => 0, // 目录风格，0：年;1：年/月;2：年/月/日
    #    'group'              => 0, // 目录内分组个数，0为不分组
    #    'path'               => 'files',// 存在路径
    #    'uploader'           => 'file',//文件上传器,
    #    'params'             => '',//文件上传器配置参数,具体值由上传器决定
    #    'watermark_pos'      => 'br',//水印位置,
    #    'watermark_min_size' => '',//加水印最小图片尺寸
    #    'transxy'            => '0x0',// 水印偏移
    #    'pngquant'           => '',// PNG图片优化工具绝对路径
    #],
    # 默认本地文件Storage配置
    #'ssn'         => 'file:path=storage',
    # 模板缓存与调试
    #'smarty'      => [
    #    'cache'    => false, //是否开启缓存
    #    'debugArg' => ''  //调试
    #],
    #'resource'    => [
    #    'combinate' => env('resource.combinate', 0),
    #    'minify'    => env('resource.minify', 0)
    #],
    # CurlClient类代理配置
    #'proxy'       => [
    #    'type' => '',
    #    'auth' => '',
    #    'host' => '',
    #    'port' => 0
    #],
];