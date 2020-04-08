<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace tests\Tests\restful;

use PHPUnit\Framework\TestCase;
use wulaphp\restful\DefaultSignChecker;
use wulaphp\util\CurlClient;

class RESTFulServerTest extends TestCase {

    public function testServerSign() {
        $arg['api']         = 'testm.hello.greeting';
        $arg['app_key']     = '123';
        $arg['v']           = 1;
        $arg['sign_method'] = 'md5';
        $arg['format']      = 'json';
        $arg['name']        = 'Leo';
        $arg['timestamp']   = date('Y-m-d H:i:s');
        $signer             = new DefaultSignChecker();
        $sign               = $signer->sign($arg, '123', 'md5');
        $arg['sign']        = $sign;
        self::assertNotEmpty($sign);

        return $arg;
    }

    public function testServerSignV2() {
        $arg['api']         = 'testm.hello.greeting';
        $arg['app_key']     = '123';
        $arg['v']           = 2;
        $arg['sign_method'] = 'md5';
        $arg['format']      = 'json';
        $arg['name']        = 'Leo';
        $arg['timestamp']   = date('Y-m-d H:i:s');
        $signer             = new DefaultSignChecker();
        $sign               = $signer->sign($arg, '123', 'md5');
        $arg['sign']        = $sign;
        self::assertNotEmpty($sign);

        return $arg;
    }
    public function testServerSign2() {
        $arg['api']         = 'testm.hello2.greeting';
        $arg['app_key']     = '123';
        $arg['v']           = 2;
        $arg['sign_method'] = 'md5';
        $arg['format']      = 'json';
        $arg['name']        = 'Leo';
        $arg['timestamp']   = date('Y-m-d H:i:s');
        $signer             = new DefaultSignChecker();
        $sign               = $signer->sign($arg, '123', 'md5');
        $arg['sign']        = $sign;
        self::assertNotEmpty($sign);

        return $arg;
    } 
    public function testServerSign1() {
        $arg['api']         = 'testm.hello.greeting';
        $arg['app_key']     = '123';
        $arg['v']           = 1;
        $arg['sign_method'] = 'md5';
        $arg['format']      = 'json';
        $arg['name']        = 'Leo';
        $arg['age']         = 0;
        $arg['timestamp']   = date('Y-m-d H:i:s');
        $signer             = new DefaultSignChecker();
        $sign               = $signer->sign($arg, '123', 'md5');
        $arg['sign']        = $sign;
        self::assertNotEmpty($sign);

        return $arg;
    }

    public function testFileSign() {
        $arg['api']         = 'testm.hello.upload';
        $arg['app_key']     = '123';
        $arg['v']           = 1;
        $arg['sign_method'] = 'md5';
        $arg['format']      = 'json';
        $arg['name']        = 'Leo';
        $arg['avatar']      = '@' . STORAGE_PATH . 'a.txt';
        $arg['timestamp']   = date('Y-m-d H:i:s');
        $signer             = new DefaultSignChecker();
        $sign               = $signer->sign($arg, '123', 'md5');
        $arg['sign']        = $sign;
        self::assertNotEmpty($sign);

        return $arg;
    }

    /**
     * @depends testServerSign
     *
     * @param $args
     */
    public function testGet($args) {
        $curlient = CurlClient::getClient(5);
        $rtn      = $curlient->get('http://127.0.0.1:9090/testm/api?' . http_build_query($args));

        $this->assertEquals('{"response":{"greeting":"Hello Leo"}}', $rtn);
    }
    /**
     * @depends testServerSign2
     *
     * @param $args
     */
    public function testV2Get($args) {
        $curlient = CurlClient::getClient(5);
        $rtn      = $curlient->get('http://127.0.0.1:9090/testm/api?' . http_build_query($args));

        $this->assertEquals('{"response":{"greeting":"Hello2 Leo"}}', $rtn);
    }
    /**
     * @depends testServerSignV2
     *
     * @param $args
     */
    public function testVerDowngradeGet($args) {
        $curlient = CurlClient::getClient(5);
        $rtn      = $curlient->get('http://127.0.0.1:9090/testm/api?' . http_build_query($args));

        $this->assertEquals('{"response":{"greeting":"Hello Leo"}}', $rtn);
    }
    /**
     * @depends testServerSign
     *
     * @param $args
     */
    public function testPost($args) {
        $curlient = CurlClient::getClient(5);
        $rtn      = $curlient->post('http://127.0.0.1:9090/testm/api', $args);

        $this->assertEquals('{"response":{"greeting":"Hello Leo"}}', $rtn);
    }

    /**
     * @depends testFileSign
     *
     * @param $args
     */
    public function testPostWithFile($args) {
        $curlient = CurlClient::getClient(5);
        $rtn      = $curlient->post('http://127.0.0.1:9090/testm/api', $args);

        $this->assertEquals('{"response":{"name":"Leo","avatar":"a.txt"}}', $rtn);
    }

    /**
     * @depends testServerSign1
     *
     * @param $args
     */
    public function testUnknownParam($args) {
        $curlient = CurlClient::getClient(5);
        $rtn      = $curlient->get('http://127.0.0.1:9090/testm/api?' . http_build_query($args));

        $this->assertTrue(strpos($rtn, '40004') > 0);
        $this->assertTrue(strpos($rtn, '"age"') > 0);
    }
}