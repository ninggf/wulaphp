<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace tests\Tests\auth;

use PHPUnit\Framework\TestCase;
use wulaphp\auth\AclResource;
use wulaphp\auth\AclResourceManager;

class AclResourceTest extends TestCase {
    public function testNewIns() {
        $acl = new AclResource('/');
        self::assertEquals('/', $acl['id']);
    }

    public function testAclMgr() {
        $acm = AclResourceManager::getInstance('admin');

        $acl = $acm->getResource('abc/def', 'DEF');
        self::assertEquals('def', $acl['id']);
        self::assertEquals('DEF', $acl->name);

        $acl = $acm->getResource('abc', 'ABC');
        self::assertEquals('abc', $acl['id']);
        self::assertEquals('ABC', $acl->name);
        $acl->addOperate('add', 'Add Abc');

        $acm->getResource('site/channel', 'Channel', 'm');
        $acm->getResource('site/page', 'Page', 'm');

        $acl = $acm->getResource('/');
        self::assertEquals('/', $acl['id']);

        $nodes = $acl->items;
        self::assertCount(2, $nodes);

        $acl   = $acm->getResource('site');
        $nodes = $acl['items'];
        self::assertCount(2, $nodes);

        $acl   = $acm->getResource('abc');
        $nodes = $acl->getNodes();
        self::assertCount(1, $nodes);
    }
}