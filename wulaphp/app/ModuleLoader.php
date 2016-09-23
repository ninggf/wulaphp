<?php
namespace wulaphp\app;

class ModuleLoader {
    public function load() {
        $it = new \DirectoryIterator ( MODULE_ROOT );
        foreach ( $it as $dir ) {
            if ($dir->isDot ()) {
                continue;
            }
            if ($dir->isDir ()) {
                $boot = MODULE_ROOT . $dir->getFilename () . '/bootstrap.php';
                if (is_file ( $boot )) {
                    $rtn = include $boot;
                    if ($rtn && is_string ( $rtn )) {
                        App::register ( $rtn, $boot );
                    }
                }
            }
        }
    }
	public function scanModules(){
		$it = new \DirectoryIterator ( MODULE_ROOT );
		foreach ( $it as $dir ) {
			if ($dir->isDot ()) {
				continue;
			}
			if ($dir->isDir ()) {
				$boot = MODULE_ROOT . $dir->getFilename () . '/bootstrap.php';
				if (is_file ( $boot )) {
					$rtn = include $boot;
					if ($rtn && is_string ( $rtn )) {
						App::register ( $rtn, $boot );
					}
				}
			}
		}
	}

    /**
     *
     * {@inheritDoc}
     *
     * @see IModuleLoader::loadClass()
     */
    public function loadClass($module, $file) {
        $clf = MODULE_ROOT . $file;
        if (is_file ( $clf )) {
            return $clf;
        }
        return null;
    }
    protected function loadit($module){

    }
}