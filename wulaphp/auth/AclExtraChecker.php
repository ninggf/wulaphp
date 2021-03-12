<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace wulaphp\auth;
/**
 * ACL额外检查器,通过继承该类可以实现自定义权限检测。
 *
 * @package wulaphp\auth
 */
abstract class AclExtraChecker {
    /**
     * @var AclExtraChecker
     */
    private $next;

    /**
     * 添加下一个检验器.
     *
     * @param AclExtraChecker $checker
     */
    public final function next(AclExtraChecker $checker) {
        $this->next = $checker;
    }

    /**
     * 权限检验.
     *
     * @param Passport   $passport
     * @param string     $op
     * @param array|null $extra
     *
     * @return bool
     */
    public final function check(Passport $passport, string $op, ?array $extra = null): bool {
        $rst = $this->doCheck($passport, $op, $extra);
        if ($rst && $this->next) {
            $rst = $this->next->check($passport, $op, $extra);
        }

        return $rst;
    }

    /**
     * 自定义校验。
     *
     * @param Passport   $passport 通行证
     * @param string     $op       操作
     * @param array|null $extra    额外数据
     *
     * @return bool
     */
    protected abstract function doCheck(Passport $passport, string $op, ?array $extra = null): bool;
}