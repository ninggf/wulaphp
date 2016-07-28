<?php

use wulaphp\app\App;
/**
 * 获取数据库对象实例以便操作数据库.
 *
 * @param string $name
 */
function db($name = null) {
    static $conns = [ ];
    if (empty ( $name )) {
        $name = 'default';
    }
    if (is_array ( $name )) {
        $setting = App::
        $dbcnfs = $setting ['database'];
        if (empty ( $name ['port'] )) {
            unset ( $name ['port'] );
        }
        if (isset ( $name ['dialect'] )) {
            $tmpname = $name ['dialect'];
        } else {
            $tmpname = 'tmp' . '_' . $name ['host'] . '_' . $name ['dbname'];
        }
        $dbcnfs [$tmpname] = $name;
        $setting ['database'] = $dbcnfs;
        $name = $tmpname;
    } else if ($name instanceof \DatabaseDialect) {
        return $name;
    }
    try {
        $name = $name ? $name : 'default';
        self::$lastErrorMassge = false;
        if (! isset ( self::$INSTANCE [$name] )) {
            $settings = KissGoSetting::getSetting ();
            if (! isset ( $settings ['database'] )) {
                trigger_error ( 'the configuration for database is not found!', E_USER_ERROR );
            }
            $database_settings = $settings ['database'];
            if (! isset ( $database_settings [$name] )) {
                trigger_error ( 'the configuration for database: ' . $name . ' is not found!', E_USER_ERROR );
            }
            $options = $database_settings [$name];
            $driver = isset ( $options ['driver'] ) && ! empty ( $options ['driver'] ) ? $options ['driver'] : 'MySQL';
            $driverClz = $driver . 'Dialect';
            if (! is_subclass_of2 ( $driverClz, 'DatabaseDialect' )) {
                trigger_error ( 'the dialect ' . $driverClz . ' is not found!', E_USER_ERROR );
            }
            $dr = new $driverClz ( $options );
            $dr->setAttribute ( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
            $dr->onConnected ();
            self::$INSTANCE [$name] = $dr;
        }
        return self::$INSTANCE [$name];
    } catch ( PDOException $e ) {
        self::$lastErrorMassge = $e->getMessage ();
        return null;
    }
    
    return $db;
}