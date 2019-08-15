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
        return 'print hooks and its handlers';
    }

    public function argDesc() {
        return '[pattern]';
    }

    protected function execute($options) {
        global $__ksg_rtk_hooks;
        $search = $this->opt(1);
        $total  = 0;
        $ht     = 0;
        foreach ($__ksg_rtk_hooks as $hook => $handlers) {
            if ($search && strpos(strtolower($hook), strtolower($search)) === false) {
                continue;
            }
            $ht++;
            echo $hook, ":\n";
            foreach ($handlers as $p => $hands) {
                echo "  priority: ", $p, "\n";
                $idx = 0;
                foreach ($hands as $hand) {
                    echo '   ', ++$idx, '. ';
                    $total++;
                    if (is_array($hand['func'])) {
                        if (is_object($hand['func'][0])) {
                            echo get_class($hand['func'][0]), '->', $hand['func'][1];
                        } else {
                            echo $hand['func'][0], '::', $hand['func'][1];
                        }
                    } else if (is_string($hand['func'])) {
                        echo $this->color->str($hand['func'], 'green'), '@';
                        echo str_replace(APPROOT, '', $hand['extra']['file']), ':', $hand['extra']['line'];
                    } else if ($hand['func'] instanceof \Closure) {
                        echo $this->color->str('anonymous function', 'green'), '@';
                        echo str_replace(APPROOT, '', $hand['extra']['file']), ':', $hand['extra']['line'];
                    }
                    echo "\n";
                }
            }
            echo "\n";
        }
        echo "Total: ", $ht, ' hooks, ', $total, " handlers\n";

        return 0;
    }
}