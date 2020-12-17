<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 *
 * 本文件定义了cmf需要用到的两张表。
 */

$tables['1.0.0'][] = "CREATE TABLE IF NOT EXISTS `{prefix}module` (
    `id` SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(32) NOT NULL COMMENT '模块ID',
    `version` VARCHAR(32) NOT NULL COMMENT '版本',
    `status` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '0禁用1启用',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '安装时间',
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '最后一次升级时间',
    `checkupdate` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '是否检测升级信息',
    `kernel` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否是内核内置模块',
    PRIMARY KEY (`id`),
    UNIQUE INDEX `UDX_NAME` (`name` ASC)
)  ENGINE=INNODB DEFAULT CHARACTER SET={encoding} COMMENT='模块表'";

$tables['1.0.0'][] = "CREATE TABLE IF NOT EXISTS `{prefix}settings` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `group` VARCHAR(24) NOT NULL COMMENT '配置组',
    `name` VARCHAR(32) NOT NULL COMMENT '字段名',
    `value` TEXT NULL COMMENT '值',
    PRIMARY KEY (`id`),
    UNIQUE INDEX `UDX_NAME` (`group` ASC , `name` ASC)
)  ENGINE=INNODB DEFAULT CHARACTER SET={encoding} COMMENT='配置表'";