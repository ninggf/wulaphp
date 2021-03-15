<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace wulaphp\cmf;

use wulaphp\app\App;
use wulaphp\app\Module;
use wulaphp\db\DatabaseConnection;
use wulaphp\db\dialect\DatabaseDialect;

/**
 * Class CmfModule
 */
abstract class CmfModule extends Module {
    public $isKernel = false;

    /**
     * 安装.
     *
     * @param DatabaseConnection $con
     * @param int                $kernel 1代表安装的是内核模块.
     *
     * @return bool
     */
    public final function install(DatabaseConnection $con, int $kernel = 0): bool {
        if ($con->select('id')->from('{module}')->where(['name' => $this->namespace])->exist('id')) {
            return true;
        }
        $con->start();
        $rst = $this->upgrade($con, $this->currentVersion);
        if ($rst) {
            $data['name']        = $this->namespace;
            $data['version']     = $this->currentVersion;
            $data['create_time'] = $data['update_time'] = time();
            $data['kernel']      = $kernel;
            $rst                 = $con->insert($data)->into('{module}')->exec(true);
        }
        if ($rst) {
            $con->commit();
        } else {
            $con->rollback();
        }

        return $rst;
    }

    /**
     * 卸载.
     * @return bool
     * @throws
     */
    public final function uninstall(): bool {
        if (!App::db()->select('id')->from('{module}')->where(['name' => $this->namespace])->exist('id')) {
            return false;
        }
        $rst = $this->onUninstall();
        if ($rst) {
            $db = App::db();
            $db->delete()->from('{module}')->where(['name' => $this->namespace])->exec();
            App::cfg();
        }

        return $rst;
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public final function stop(): bool {
        if (!App::db()->select('id')->from('{module}')->where(['name' => $this->namespace])->exist('id')) {
            return false;
        }

        App:: db()->update('{module}')->set(['status' => 0])->where([
            'name'   => $this->namespace,
            'kernel' => 0
        ])->exec();

        return true;
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public final function start(): bool {
        if (!App::db()->select('id')->from('{module}')->where(['name' => $this->namespace])->exist('id')) {
            return false;
        }

        App:: db()->update('{module}')->set(['status' => 1])->where([
            'name' => $this->namespace
        ])->exec();

        return true;
    }

    /**
     * @param DatabaseConnection $db
     * @param string             $toVer
     * @param string             $fromVer
     *
     * @return bool
     * @throws
     */
    public final function upgrade(DatabaseConnection $db, string $toVer, string $fromVer = '0.0.0'): bool {
        if ($fromVer !== '0.0.0' && !$db->select('id')->from('{module}')->where(['name' => $this->namespace])->exist('id')) {
            return false;
        }
        $db->start();
        try {
            $prev = $fromVer;
            foreach ($this->getVersionList() as $ver => $chang) {
                $func = 'upgradeTo' . str_replace('.', '_', $ver);
                if (version_compare($ver, $toVer, '<=') && version_compare($ver, $fromVer, '>')) {
                    $sqls = $this->getSchemaSQLs($db->getDialect(), $ver, $prev);
                    $prev = $ver;
                    if ($sqls) {
                        $sr = ['{prefix}', '{encoding}'];
                        $rp = [$db->getDialect()->getTablePrefix(), $db->getDialect()->getCharset()];
                        foreach ($sqls as $_sql) {
                            if (!$_sql) {
                                continue;
                            }
                            $_sql = (array)$_sql;
                            foreach ($_sql as $sql) {
                                $sql = str_replace($sr, $rp, $sql);
                                $rst = $db->exec($sql);
                                if (!$rst) {
                                    throw_exception($db->error);
                                }
                            }
                        }
                    }
                    if ($func && method_exists($this, $func)) {
                        $rst = $this->{$func}($db);
                        if (!$rst) {
                            throw_exception('upgradeTo func error');
                        }
                    }
                }
            }

            if ($fromVer != '0.0.0') {
                $db->update('{module}')->set(['version' => $toVer])->where(['name' => $this->namespace])->exec();
            }
            $db->commit();
        } catch (\Exception $e) {
            $db->rollback();
            throw_exception($e);
        }

        return true;
    }

    /**
     * 取当前模板所定义的表.
     *
     * @param \wulaphp\db\dialect\DatabaseDialect $dialect
     *
     * @return array
     */
    public function getDefinedTables(DatabaseDialect $dialect): array {
        $sqlFile = MODULES_PATH . $this->dirname . DS . $dialect->getDriverName() . '.sql.php';
        if (is_file($sqlFile)) {
            $file = file_get_contents($sqlFile);

            return $dialect->getTablesFromSQL($file);
        }

        return [];
    }

    protected function onUninstall(): bool {
        return true;
    }

    /**
     * 加载SQL语句
     *
     * @param DatabaseDialect $dialect
     * @param string          $toVer
     * @param string          $fromVer
     *
     * @return array
     */
    protected final function getSchemaSQLs(DatabaseDialect $dialect, string $toVer, string $fromVer = '0.0.0'): array {
        $sqls    = [];
        $sqlFile = MODULES_PATH . $this->dirname . DS . $dialect->getDriverName() . '.sql.php';
        if (is_file($sqlFile)) {
            $tables = [];
            @include $sqlFile;
            if (!empty ($tables)) {
                foreach ($tables as $ver => $var) {
                    if ($var && version_compare($ver, $toVer, '<=') && version_compare($ver, $fromVer, '>')) {
                        $sqls = array_merge($sqls, (array)$var);
                    }
                }
            }
        }

        return $sqls;
    }
}