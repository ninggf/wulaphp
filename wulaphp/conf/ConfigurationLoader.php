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
     * @see \wulaphp\conf\BaseConfigurationLoader::loadConfig()
     * @return \wulaphp\conf\Configuration
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
        // 再给用户一次机会
        $cfg = apply_filter('on_load_' . $name . '_config', $config);

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
        if (empty($name)) {
            return new Configuration('');
        } else {
            $loader = new ConfigurationLoader();

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

        // 再给用户一次机会
        $cfg = apply_filter('on_load_' . $name . '_dbconfig', $config);

        return $cfg instanceof DatabaseConfiguration ? $cfg : $config;
    }
}