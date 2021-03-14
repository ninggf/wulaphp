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

use wulaphp\app\App;
use wulaphp\db\DatabaseConnection;
use wulaphp\form\FormTable;
use wulaphp\router\UrlParsedInfo;

/**
 * cts数据源.
 *
 * @package wulaphp\mvc\model
 */
abstract class CtsDataSource {
    private static $forms = [];

    /**
     * 获取数据.
     *
     * @param array                         $con      条件.
     * @param string|null                   $db       数据库
     * @param \wulaphp\router\UrlParsedInfo $pageInfo 分页信息
     * @param array                         $tplvars  模板变量.
     *
     * @return CtsData 数据.
     */
    public final function getList(array $con, ?string $db, UrlParsedInfo $pageInfo, array $tplvars): CtsData {
        try {
            $dbx = null;
            if ($db != 'null') {
                $dbx = App::db($db);
            }
            $data = $this->getData($con, $dbx, $pageInfo, $tplvars);

            if ($data instanceof CtsData) {
                return $data;
            }
        } catch (\Exception $e) {

        }

        return new CtsData([], 0);
    }

    /**
     * 数据源名称
     *
     * @return string
     */
    public abstract function getName(): string;

    /**
     * 取数据
     *
     * @param array                               $con
     * @param \wulaphp\db\DatabaseConnection|null $db
     * @param \wulaphp\router\UrlParsedInfo       $pageInfo
     * @param array                               $tplvar
     *
     * @return CtsData
     */
    protected abstract function getData(array $con, ?DatabaseConnection $db, UrlParsedInfo $pageInfo, array $tplvar): CtsData;

    /**
     * 条件表单.
     *
     * @return null|\wulaphp\form\FormTable
     */
    public function getCondForm() {
        $clz = static::class;
        if (isset(self::$forms[ $clz ])) {
            $form = self::$forms[ $clz ];

            return $form;
        }

        return null;
    }

    /**
     * 定义变量名.
     *
     * @return string 变量名.
     */
    public function getVarName(): string {
        return 'item';
    }

    /**
     * 预览列表定义.
     * @return array
     */
    public function getCols(): array {
        return ['value' => __('Value')];
    }

    /**
     * 注册数据源条件表单.
     *
     * @param string    $clz
     * @param FormTable $form
     */
    public static function registerCondForm(string $clz, $form) {
        assert(!empty($clz) && class_exists($clz), 'CtsDataSource is invalid');
        assert($form instanceof FormTable, get_class($form) . ' is not an instance of FormTable');
        self::$forms[ $clz ] = $form;
    }
}