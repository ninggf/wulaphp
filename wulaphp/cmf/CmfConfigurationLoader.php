<?php
/*
 * 内容管理框架配置加载器.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace wulaphp\cmf;

use wulaphp\conf\Configuration;
use wulaphp\conf\ConfigurationLoader;

class CmfConfigurationLoader extends ConfigurationLoader {
    public function __construct() {
        //检测是否安装.
        if (is_file(CONFIG_PATH . 'install.lock')) {
            define('WULACMF_INSTALLED', true);
        } else {
            define('WULACMF_INSTALLED', false);
        }
    }

    /**
     * @param string $name
     *
     * @return \wulaphp\conf\Configuration
     */
    public function loadConfig(string $name = 'default'): Configuration {
        //优先从文件加载
        $config = parent::loadConfig($name);
        if (!defined('DEBUG') && $name == 'default') {
            $debug = $config->get('debug', DEBUG_ERROR);
            if ($debug > 1000 || $debug < 0) {
                $debug = DEBUG_OFF;
            }
            define('DEBUG', $debug);
        }

        return $config;
    }
}