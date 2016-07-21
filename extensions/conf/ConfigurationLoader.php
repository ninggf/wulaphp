<?php
namespace extensions\conf;

use wulaphp\conf\BaseConfigurationLoader;
use wulaphp\conf\Configuration;

/**
 * 系统默认配置加载器.
 *
 * @author leo
 *
 */
class ConfigurationLoader extends BaseConfigurationLoader {

    /**
     * 加载配置.
     * {@inheritDoc}
     *
     * @see \wulaphp\conf\BaseConfigurationLoader::loadConfig()
     */
    public function loadConfig($name = 'default') {
        $config = new Configuration ( $name );
        if ($name == 'default') {
            $_wula_config_file = APPROOT . CONF_DIR . '/config';
        } else {
            $_wula_config_file = APPROOT . CONF_DIR . '/' . $name . '_config';
        }
        $wula_cfg_fiels [] = $_wula_config_file . '_' . APP_MODE . '.php';
        $wula_cfg_fiels [] = $_wula_config_file . '.php';
        foreach ( $wula_cfg_fiels as $_wula_config_file ) {
            if (is_file ( $_wula_config_file )) {
                include $_wula_config_file;
                break;
            }
        }
        unset ( $_wula_config_file, $wula_cfg_fiels );
        return $config;
    }

    /**
     * 加载数据库配置.
     * {@inheritDoc}
     *
     * @see \wulaphp\conf\BaseConfigurationLoader::loadDatabaseConfig()
     */
    public function loadDatabaseConfig($name = '') {
        // TODO Auto-generated method stub
    }
}