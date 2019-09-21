<?php

namespace wulaphp\conf;

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
     * @return \wulaphp\conf\Configuration
     * @see \wulaphp\conf\BaseConfigurationLoader::loadConfig()
     */
    public function loadConfig($name = 'default') {
        $config = new Configuration($name);
        if ($name == 'default') {
            $_wula_config_file = APPROOT . CONF_DIR . '/config';
        } else {
            $_wula_config_file = APPROOT . CONF_DIR . '/' . $name . '_config';
        }
        $wula_cfg_fiels [] = $_wula_config_file . '.php';
        $wula_cfg_fiels [] = $_wula_config_file . '_' . APP_MODE . '.php';
        foreach ($wula_cfg_fiels as $_wula_config_file) {
            if (is_file($_wula_config_file)) {
                $cfg = include $_wula_config_file;
                if ($cfg instanceof Configuration) {
                    $config->setConfigs($cfg->toArray());
                } else if (is_array($cfg)) {
                    $config->setConfigs($cfg);
                }
            }
        }
        unset ($_wula_config_file, $wula_cfg_fiels);

        if ($name == 'default' && !defined('DEBUG')) {
            $debug = intval($config->get('debug', DEBUG_ERROR));
            if ($debug > 1000 || $debug < 0) {
                $debug = DEBUG_OFF;
            }
            define('DEBUG', $debug);
        }

        // 再给用户一次机会
        $cfg = function_exists('apply_filter') ? apply_filter('on_load_' . $name . '_config', $config) : $config;

        return $cfg instanceof Configuration ? $cfg : $config;
    }

    /**
     * 从配置文件加载.
     *
     * @param string $name
     *
     * @return \wulaphp\conf\Configuration
     */
    public static function loadFromFile($name) {
        static $loader = null;
        if ($loader === null) {
            $loader = new ConfigurationLoader();
        }
        if (empty($name)) {
            return new Configuration('');
        } else {
            return $loader->loadConfig($name);
        }
    }

    /**
     * 加载数据库配置.
     *
     * @param string $name
     *
     * @return DatabaseConfiguration
     */
    public function loadDatabaseConfig($name = 'default') {
        $config = new DatabaseConfiguration ($name);
        if ($name == 'default') {
            $_wula_config_file = APPROOT . CONF_DIR . '/dbconfig';
        } else {
            $_wula_config_file = APPROOT . CONF_DIR . '/' . $name . '_dbconfig';
        }
        $wula_cfg_fiels [] = $_wula_config_file . '.php';
        $wula_cfg_fiels [] = $_wula_config_file . '_' . APP_MODE . '.php';
        foreach ($wula_cfg_fiels as $_wula_config_file) {
            if (is_file($_wula_config_file)) {
                $cfg = include $_wula_config_file;
                if ($cfg instanceof Configuration) {
                    $config->setConfigs($cfg);
                } else if (is_array($cfg)) {
                    $config->setConfigs($cfg);
                }
            }
        }
        unset ($_wula_config_file, $wula_cfg_fiels);

        return $config;
    }
}