<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace wulaphp\io;

use wulaphp\app\App;

/**
 * Class Cookie
 * @package wulaphp\io
 * @internal
 */
class Cookie implements \ArrayAccess {
    private $name;
    private $value;
    private $path;
    private $expire;
    private $domain;
    private $security = false;
    private $httponly = false;
    private $samesite = 'None';

    public function __construct($name, $value) {
        $cks            = self::pv();
        $this->name     = $name;
        $this->value    = $value;
        $exp            = $cks['expire'];
        $this->expire   = $exp ? time() + $exp : 0;
        $this->path     = $cks['path'];
        $this->domain   = $cks['domain'];
        $this->security = $cks['security'] ?? false;
        $this->httponly = $cks['httponly'] ?? false;
        $this->samesite = $cks['SameSite'] ?? 'None';
    }

    /**
     * @param string $path
     *
     * @return \wulaphp\io\Cookie
     */
    public function path(string $path): Cookie {
        $this->path = $path;

        return $this;
    }

    /**
     * @param string $domain
     *
     * @return \wulaphp\io\Cookie
     */
    public function domain(string $domain): Cookie {
        $this->domain = $domain;

        return $this;
    }

    /**
     * @param int $expire
     *
     * @return \wulaphp\io\Cookie
     */
    public function expire(int $expire): Cookie {
        $exp          = time() + $expire;
        $this->expire = $exp;

        return $this;
    }

    /**
     * @param bool $security
     *
     * @return \wulaphp\io\Cookie
     */
    public function security(bool $security): Cookie {
        $this->security = $security;

        return $this;
    }

    /**
     * @param bool $httponly
     *
     * @return \wulaphp\io\Cookie
     */
    public function httponly(bool $httponly): Cookie {
        $this->httponly = $httponly;

        return $this;
    }

    /**
     * @param string $policy
     *
     * @return \wulaphp\io\Cookie
     */
    public function samesite(string $policy): Cookie {
        $this->samesite = $policy;

        return $this;
    }

    public function offsetExists($offset) {
        return false;
    }

    public function offsetGet($offset) {
        return $this->{$offset};
    }

    public function offsetSet($offset, $value) {
    }

    public function offsetUnset($offset) {
    }

    /**
     *
     * @return array
     */
    private static function &pv(): array {
        static $cookie_setting = false;
        if ($cookie_setting === false) {
            $settings       = App::cfg();
            $cookie_setting = array_merge2([
                'expire'   => 0,
                'path'     => '/',
                'domain'   => '',
                'security' => false,
                'httponly' => false,
                'SameSite' => 'None'
            ], $settings->get('cookie', []));
        }

        return $cookie_setting;
    }
}