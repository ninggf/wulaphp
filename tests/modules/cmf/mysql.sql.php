<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

$tables['1.0.0'][] = "CREATE TABLE IF not EXISTS `{prefix}cmf_table` (id int)";

$tables['1.0.0'][] = "CREATE table `{prefix}cmf_table1` (id int)";

return $tables;