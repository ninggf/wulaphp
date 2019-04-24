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
use wulaphp\restful\RESTFulClient;

class RESTFulClientTest extends TestCase {

    public function testGet() {
        $rest = new RESTFulClient('http://127.0.0.1:9090/testm/api', '123', '123', '1');

        $args['name'] = 'Leo';
        $rtn          = $rest->get('testm.hello.greeting', $args)->getReturn();

        $this->assertArrayHasKey('response', $rtn);
        $this->assertEquals('Hello Leo', $rtn['response']['greeting']);
    }

    public function testPost() {
        $rest         = new RESTFulClient('http://127.0.0.1:9090/testm/api', '123', '123', '1');
        $args['name'] = 'Leo';
        $rtn          = $rest->post('testm.hello.greeting', $args)->getReturn();

        $this->assertArrayHasKey('response', $rtn);
        $this->assertEquals('Hello Leo', $rtn['response']['greeting']);
    }

    public function testPostWithFile() {
        $rest           = new RESTFulClient('http://127.0.0.1:9090/testm/api', '123', '123', '1');
        $args['name']   = 'Leo';
        $args['avatar'] = '@' . STORAGE_PATH . 'a.txt';
        $rtn            = $rest->post('testm.hello.upload', $args)->getReturn();

        $this->assertArrayHasKey('response', $rtn);
        $this->assertEquals('Leo', $rtn['response']['name']);
        $this->assertEquals('a.txt', $rtn['response']['avatar']);
    }

    public function testPosts() {
        $rest          = new RESTFulClient('http://127.0.0.1:9090/testm/api', '123', '123', '1');
        $args1['name'] = 'Leo';

        $args2['name']   = 'Leo';
        $args2['avatar'] = '@' . STORAGE_PATH . 'a.txt';

        $rtns = $rest->posts(['testm.hello.greeting', 'testm.hello.upload'], [$args1, $args2]);

        $this->assertNotEmpty($rtns);

        $this->assertArrayHasKey('response', $rtns[0]);
        $this->assertEquals('Hello Leo', $rtns[0]['response']['greeting']);

        $this->assertArrayHasKey('response', $rtns[1]);
        $this->assertEquals('Leo', $rtns[1]['response']['name']);
        $this->assertEquals('a.txt', $rtns[1]['response']['avatar']);
    }

    public function testRt() {
        $rest = new RESTFulClient('http://127.0.0.1:9090/testm/api', '123', '123', '1');
        $rtn  = $rest->get('testm.hello.rt')->getReturn();

        $this->assertArrayHasKey('response', $rtn);
        if (extension_loaded('yac') || extension_loaded('apc') || extension_loaded('apcu') || extension_loaded('xcache')) {
            $this->assertStringEndsWith('wulaphp/restful/API.php', $rtn['response']['file']);
        } else {
            $this->assertEmpty($rtn['response']['file']);
        }
    }
}