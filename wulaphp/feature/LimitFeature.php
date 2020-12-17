<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace wulaphp\feature;

use Exception;
use wulaphp\conf\ConfigurationLoader;
use wulaphp\io\Request;
use wulaphp\io\Response;
use wulaphp\util\RedisClient;

/**
 * 防CC特性.
 *
 * @package wulaphp\feature
 */
class LimitFeature implements ICmsFeature {
    private $limit;
    private $inter;

    /**
     * LimitFeature constructor.
     *
     * @param int $limit
     * @param int $interval
     */
    public function __construct(int $limit, int $interval) {
        $this->limit = $limit;
        $this->inter = $interval;
    }

    public function getPriority(): int {
        return 0;
    }

    public function getId(): string {
        return 'limit';
    }

    public function perform(string $url): bool {
        //防CC,取IP
        $ip = Request::getIp();
        if (defined('ANTI_CC_WHITE') && ANTI_CC_WHITE) {
            $whites = explode(',', ANTI_CC_WHITE);
            if (in_array($ip, $whites)) {
                return true;
            }
        }
        $arg[0]    = $this->limit;
        $arg[1]    = $this->inter;
        $cfgLoader = new ConfigurationLoader();
        $cfg       = $cfgLoader->loadConfig('ccredis');
        $cnf       = [
            $cfg->get('host'),
            $cfg->geti('port'),
            $cfg->geti('timeout', 3),
            $cfg->get('auth'),
            $cfg->geti('db')
        ];
        try {
            $redis = RedisClient::getRedis($cnf);
            if ($redis) {
                $key = 'c.' . ip2long($ip) . ':' . ceil(time() / $arg[1]);
                $cnt = $redis->incr($key);
                $redis->expire($key, $arg[1]);
                if ($cnt > $arg[0]) {//访问太快了
                    Response::respond(403);
                }
            }
        } catch (Exception $e) {

        }

        return true;
    }
}