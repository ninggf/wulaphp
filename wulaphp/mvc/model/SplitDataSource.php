<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace wulaphp\mvc\model;

use wulaphp\db\DatabaseConnection;
use wulaphp\router\UrlParsedInfo;

/**
 * 分隔content参数指定的内容.
 *
 * @package wulaphp\mvc\model
 */
class SplitDataSource extends CtsDataSource {
    public function getName(): string {
        return 'implode';
    }

    /**
     * @param array                               $con
     * @param \wulaphp\db\DatabaseConnection|null $db
     * @param \wulaphp\router\UrlParsedInfo       $pageInfo
     * @param array                               $tplvar
     *
     * @return \wulaphp\mvc\model\CtsData
     */
    protected function getData(array $con, ?DatabaseConnection $db, UrlParsedInfo $pageInfo, array $tplvar): CtsData {
        $content = aryget('content', $con);
        if (!$content) {
            return new CtsData([], 0);
        }
        if (isset($con['reg']) && $con['reg']) {
            $content = @preg_split('#' . str_replace('#', '\\#', $con['r']) . '#', $content);
        } else {
            $sp      = aryget('sp', $con, ',');
            $content = @explode($sp, $content);
        }

        return new CtsData($content, count($content));
    }

    public function getCols(): array {
        return [0 => 'Value'];
    }
}