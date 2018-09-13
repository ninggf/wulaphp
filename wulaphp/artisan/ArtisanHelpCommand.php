<?php

namespace wulaphp\artisan;

class ArtisanHelpCommand extends ArtisanCommand {
    public function help($message = '') {
        $color = new Colors();
        global $commands;
        if ($message) {
            echo "\n" . wordwrap($message, 80, "\n") . "\n\n";
        } else {
            echo "\nartisan tool for wulaphp\n\n";
        }
        echo $color->str("Usage:", 'green');
        echo " #php artisan <command> [options] [args]\n\n";

        $cmds      = array_keys($commands);
        $maxlength = strlen('-h, --help');
        array_walk($cmds, function ($value) use (&$maxlength) {
            $len = strlen($value);
            if ($len > $maxlength) $maxlength = $len;
        });
        $leftPad = str_pad('', $maxlength + 7, ' ', STR_PAD_LEFT);
        $sMax    = 77 - $maxlength;
        echo "Options:\n";
        echo '  ', str_pad('-h, --help', $maxlength + 5, ' ', STR_PAD_RIGHT), "display this help message\n";
        echo '  ', str_pad('-v', $maxlength + 5, ' ', STR_PAD_RIGHT), "display wulaphp version\n\n";
        echo "Commands:\n";
        /** @var ArtisanCommand $cmd 命令实例 */
        foreach ($commands as $name => $cmd) {
            echo "  ", $color->str(str_pad($name, $maxlength + 5, ' ', STR_PAD_RIGHT), 'green');
            $desc = $cmd->desc();
            $line = substr($desc, 0, $sMax);
            echo $line;
            $i = 1;
            while ($line = substr($desc, $sMax * $i, $sMax)) {
                echo "\n", $leftPad, $line;
                $i++;
            }
            echo "\n";
        }
        echo "\nRun  '#php artisan help <command>' for more information on a command.\n";
        if ($message) {
            exit(1);
        } else {
            exit(0);
        }
    }

    protected function execute($options) {
        global $argv, $commands;
        /** @var ArtisanCommand $cmd 命令实例 */
        if (isset($argv[2]) && isset($commands[ $argv[2] ])) {
            $cmd = $commands[ $argv[2] ];
            $cmd->help();
        } else {
            $this->help();
        }
    }

    public function desc() {
        return 'show this text or show a command help text';
    }

    public function cmd() {
        return 'help';
    }
}