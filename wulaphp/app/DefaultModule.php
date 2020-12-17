<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace wulaphp\app;
/**
 * Class DefaultModule
 * @package wulaphp\app
 * @internal
 */
class DefaultModule extends Module {
    protected $mName;

    public function __construct($ns) {
        parent::__construct();
        $this->namespace = $ns;
        $this->path      = MODULES_PATH . $ns;
        $this->hookPath  = $this->path . DS . 'hooks' . DS;
        $this->hasHooks  = is_dir($this->hookPath);
        $this->dirname   = basename($this->path);
        $this->mName     = ucwords($ns, '_');
    }

    public function getName(): string {
        return $this->mName;
    }

    public function getDescription(): string {
        return 'simple module without bootstrap.php file';
    }
}