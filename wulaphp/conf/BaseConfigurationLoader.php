<?php
namespace wulaphp\conf;

/**
 * 配置加载器.
 *
 * @author leo
 *
 */
abstract class BaseConfigurationLoader {

	/**
	 * 加载配置之前.
	 */
	public function beforeLoad() {
	}

	/**
	 * 加载配置之后.
	 */
	public function postLoad() {
	}

	/**
	 * 加载程序运行配置.
	 *
	 * @param string $name 配置名.
	 *
	 * @return Configuration 配置实例.
	 */
	public abstract function loadConfig($name = 'default');

	/**
	 * 加载数据库配置实例.
	 *
	 * @param string $name 配置名.
	 *
	 * @return DatabaseConfiguration 数据库配置实例.
	 */
	public abstract function loadDatabaseConfig($name = '');

	/**
	 * 保存配置.
	 *
	 * @param Configuration $configuration 要保存的配置实例.
	 *
	 * @return bool 成功返回true,返回失败原因.
	 */
	public function save(Configuration $configuration) {
		//保存配置逻辑.

		return true;
	}
}
