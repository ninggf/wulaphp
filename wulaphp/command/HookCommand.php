<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace wulaphp\command;

use wulaphp\artisan\ArtisanCommand;

class HookCommand extends ArtisanCommand {
    public function cmd() {
        return 'hook';
    }

    public function desc() {
        return '';
    }

    protected function execute($options) {
        define('');
        echo "hook\n";
    }
}