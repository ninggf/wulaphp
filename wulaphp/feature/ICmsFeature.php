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
 * CMS特性.
 *
 * @package wulaphp\feature
 */
interface ICmsFeature {
    public function getPriority(): int;

    public function getId(): string;

    public function perform(string $url): bool;
}