<?php
namespace wulaphp\util;

class RedisClient {

    public static function getRedis($cnf, $database = 0, $prefix = '') {
        if (is_string ( $cnf )) {
            $cnf = array (
                $cnf,6379
            );
        }
        if (count ( $cnf ) == 1) {
            $cnf [1] = 6379;
        }
<<<<<<< HEAD
        $redis = new \Redis ();
=======
        $redis = new Redis ();
>>>>>>> d465f215465f717072bb66f0ee650bf4b4a7de87
        if (count ( $cnf ) > 2) {
            $rst = $redis->connect ( $cnf [0], $cnf [1], $cnf [2] );
        } else {
            $rst = $redis->connect ( $cnf [0], $cnf [1] );
        }
        if ($rst) {
            $redis->select ( $database );
<<<<<<< HEAD
            $redis->setOption ( \Redis::OPT_SERIALIZER, \Redis::SERIALIZER_PHP );
            if ($prefix) {
                $redis->setOption ( \Redis::OPT_PREFIX, $prefix . ':' );
=======
            $redis->setOption ( Redis::OPT_SERIALIZER, Redis::SERIALIZER_PHP );
            if ($prefix) {
                $redis->setOption ( Redis::OPT_PREFIX, $prefix . ':' );
>>>>>>> d465f215465f717072bb66f0ee650bf4b4a7de87
            }
            return $redis;
        } else {
            return null;
        }
    }
}