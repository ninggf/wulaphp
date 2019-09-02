<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace testm\controllers;

use wulaphp\mvc\controller\Controller;
use wulaphp\restful\DefaultSignChecker;
use wulaphp\restful\ISecretCheck;
use wulaphp\restful\RESTFulServer;

class ApiController extends Controller {
    public function index() {
        $sign   = new DefaultSignChecker();
        $server = new RESTFulServer(new SecretChecker(), $sign);

        return $server->run();
    }
}

class SecretChecker implements ISecretCheck {
    public function check(string $appId): string {
        return $appId;
    }
}