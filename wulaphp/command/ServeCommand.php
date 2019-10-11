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

/**
 * Class ServeCommand
 * @package wulaphp\command
 * @internal
 */
class ServeCommand extends ArtisanCommand {
    private $proc;
    private $descriptorspec;

    public function cmd() {
        return 'serve';
    }

    public function desc() {
        return 'run php built-in Development server';
    }

    protected function argDesc() {
        return '[[host:]port]';
    }

    protected function execute($options) {
        $opt = $this->opt(0);
        $cmd = escapeshellcmd(PHP_BINARY);

        if ($opt) {
            $arg = escapeshellarg($opt) . ' index.php';
        } else {
            $arg = escapeshellarg('127.0.0.1:8080') . ' index.php';
        }

        $this->proc           = $cmd . ' -S ' . $arg;
        $this->descriptorspec = [
            0 => STDIN,
            1 => STDOUT,
            2 => STDERR
        ];

        if (($r = proc_open($this->proc, $this->descriptorspec, $pipes, WWWROOT))) {
            while (true) {
                sleep(5);
                $status = proc_get_status($r);
                if (!$status || !$status['running']) {
                    break;
                }
            }
        }

        return 0;
    }
}