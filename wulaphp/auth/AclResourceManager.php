<?php
namespace wulaphp\auth;
/**
 * Acl资源管理器.
 *
 * @package wulaphp\auth
 */
class AclResourceManager {

	private $root;

	public function __construct() {
		$this->root = new AclResource ('/');
	}

	/**
	 *
	 * @param string $id
	 * @param string $name
	 *
	 * @return AclResource
	 */
	public function getResource($id = '', $name = '') {
		if (empty ($id) || $id == '/') {
			return $this->root;
		} else {
			$ids  = explode('/', $id);
			$node = $this->root;
			$path = array();
			while (($id = array_shift($ids)) != null) {
				$path [] = $id;
				$node    = $node->getNode($id, implode('/', $path));
			}
			if (!empty ($name)) {
				$node->setName($name);
			}

			return $node;
		}
	}

	/**
	 * 取AclResourceManager实例.
	 *
	 * @param string $type
	 *
	 * @return AclResourceManager
	 */
	public static function getInstance($type = 'default') {
		static $aclm = [];
		if (!isset($aclm)) {
			$manager = new AclResourceManager();
			file('rbac\init' . ucfirst($type) . 'Acl', $manager);
			$aclm[ $type ] = $manager;
		}

		return $aclm[ $type ];
	}
}