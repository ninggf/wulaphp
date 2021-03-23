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
/**
 * 特性管理器.
 *
 * @package wulaphp\feature
 */
class CmsFeatureManager {
    private static $features = [];

    /**
     * 注册一个特性.
     *
     * @param \wulaphp\feature\ICmsFeature $feature
     */
    public static function register(ICmsFeature $feature) {
        self::$features[ $feature->getPriority() ][ $feature->getId() ] = $feature;
    }

    /**
     * 获取特性列表.
     *
     * @return \wulaphp\feature\ICmsFeature[][]
     */
    public static function getFeatures(): array {
        return self::$features;
    }
}