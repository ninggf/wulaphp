<?php
/*
 * 集群运行时配置
 *
 *
 * 如果要使用此配置，请在bootstrap.php文件中取消
 * define('RUN_IN_CLUSTER', true)前的注释.
 */
$config = new \wulaphp\conf\ClusterConfiguration();

$config->enabled(env('app.cluster'));
$config->addRedisServer('the host of redis server');

return $config;