<?php
/**
 * 应用配置文件.
 */
return [
    'debug'    => env('app.logger.level', 'debug'),
    'resource' => [
        'combinate' => env('resource.combinate', 0),
        'minify'    => env('resource.minify', 0)
    ]
];