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

/**
 * 分隔content参数指定的内容.
 *
 * @package wulaphp\mvc\model
 */
class SplitDataSource extends CtsDataSource {
    public function getName() {
        return '分隔数据';
    }

    /**
     * @param array                          $con
     * @param \wulaphp\db\DatabaseConnection $db
     * @param \wulaphp\router\UrlParsedInfo  $pageInfo
     * @param array                          $tplvar
     *
     * @return \wulaphp\mvc\model\CtsData
     */
    protected function getData($con, $db, $pageInfo, $tplvar) {
        $content = aryget('content', $con);
        if (!$content) {
            return new CtsData([], 0);
        }
        $sp = aryget('sp', $con, ',');
        if (isset($con['r']) && $con['r']) {
            $content = @preg_split('#' . $con['r'] . '#', $content);
        } else {
            $content = @explode($sp, $content);
        }
        $contents = [];
        foreach ($content as $c) {
            $contents[] = ['val' => $c];
        }

        return new CtsData($contents, count($contents));
    }

    public function getCols() {
        return ['val' => '值'];
    }
}