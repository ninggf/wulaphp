<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace app\controllers;

use wulaphp\app\App;
use wulaphp\cache\Cache;
use wulaphp\conf\CacheConfiguration;
use wulaphp\conf\DatabaseConfiguration;
use wulaphp\mvc\controller\Controller;
use wulaphp\util\RedisClient;

class PersistentController extends Controller {
    public function redis() {
        $redis = RedisClient::getRedis(['localhost', 6379, 5, '', 8, true]);

        $redis->set('testPst', 'persitent');
        echo "ok1 => " . date('Y-m-d H:i:s');
        #sleep(60);

        return 'ok => ' . date('Y-m-d H:i:s');
    }

    public function getredis() {
        $redis = RedisClient::getRedis(['localhost', 6379, 5, '', 8, true]);

        return $redis->get('testPst') . ' => ' . date('Y-m-d H:i:s');
    }

    public function setmem() {
        bind('on_load_cache_config', function ($conf) {
            $conf = new CacheConfiguration();
            $conf->enabled();
            $conf->addMemcachedServer('localhost');
            $conf->setDefaultCache(CACHE_TYPE_MEMCACHED);
            $conf->persistent();

            return $conf;
        });

        $cache = Cache::getCache();

        $cache->add('cachePst', 'cached');

        #sleep(60);

        return 'set => ' . date('Y-m-d H:i:s');
    }

    public function mem() {
        bind('on_load_cache_config', function ($conf) {
            $conf = new CacheConfiguration();
            $conf->enabled();
            $conf->addMemcachedServer('localhost');
            $conf->setDefaultCache(CACHE_TYPE_MEMCACHED);
            $conf->persistent();

            return $conf;
        });

        $cache = Cache::getCache();

        return $cache->get('cachePst') . ' get => ' . date('Y-m-d H:i:s');
    }

    public function db() {
        $cf = new DatabaseConfiguration();
        $cf->persistent();
        $cf->host('mysql');
        $cf->port(3306);
        $cf->user('root');
        $cf->password('');
        $cf->dbname('testx');
        $db = App::db($cf);

        $rs = $db->query('select * from t1 where id = 1');

        return view(['rows' => $rs]);
    }

    public function psql() {
        $cf = new DatabaseConfiguration();
        $cf->driver('Postgres');
        $cf->persistent();
        $cf->host('localhost');
        $cf->port(5432);
        $cf->user('postgres');
        $cf->password('');
        $cf->dbname('hzvwj_db');

        $db = App::db($cf);

        $rs = $db->queryOne('select * from cate where id = 1');

        $rs['time'] = date('Y-m-d H:i:s');

        return $rs;
    }
}