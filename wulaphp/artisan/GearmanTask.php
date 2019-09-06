<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace wulaphp\artisan;

/**
 * Gearman Task.
 *
 * @package wulaphp\artisan;
 */
class GearmanTask {
    protected $timeout;
    protected $host;
    protected $port;
    protected $client;

    /**
     * GearmanTask constructor.
     *
     * @param string $host
     * @param int    $port
     * @param int    $timeout
     *
     * @throws \Exception when cannot connect to gearmand server
     */
    public function __construct(string $host = 'localhost', int $port = 4730, int $timeout = 5) {
        $this->host    = $host;
        $this->port    = $port;
        $this->timeout = $timeout;
        $this->client  = $this->getGearmanClient();
        if (!$this->client) {
            throw new \Exception("cannot connect to gearmand server $host:$port");
        }
    }

    /**
     * 获取一个GearmanClient.
     *
     * @return \GearmanClient
     */
    protected function getGearmanClient(): ?\GearmanClient {
        $client = new \GearmanClient();
        $client->setTimeout($this->timeout * 1000);
        if ($client->addServer($this->host, $this->port)) {
            return $client;
        }

        return null;
    }

    /**
     *
     * @param string       $job
     * @param string|array $args
     * @param null|string  $id
     *
     * @return string
     */
    public function doBackground(string $job, $args, ?string $id = null): ?string {
        if (is_array($args)) {
            $args = json_encode($args);
        }

        return $this->client->doBackground($job, $args, $id);
    }

    /**
     * @param string       $job
     * @param array|string $args
     * @param string       $id
     *
     * @return string|null
     */
    public function doHigh(string $job, $args, ?string $id = null): ?string {
        if (is_array($args)) {
            $args = json_encode($args);
        }

        return $this->client->doHigh($job, $args, $id);
    }

    /**
     *
     * @param string       $job
     * @param string|array $args
     * @param null|string  $id
     *
     * @return string
     */
    public function doHighBackground(string $job, $args, ?string $id = null): ?string {
        if (is_array($args)) {
            $args = json_encode($args);
        }

        return $this->client->doHighBackground($job, $args, $id);
    }
}