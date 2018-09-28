<?php

namespace wulaphp\artisan;
/**
 * 命令.
 *
 * @author  leo <windywany@gmail.com>
 * @package wulaphp\artisan
 * @since   1.0
 */
abstract class ArtisanCommand {
    protected $pcmd;//上级命令
    protected $pid     = '';
    protected $color;
    protected $argv    = [];
    protected $arvc    = 0;
    protected $subCmds = [];

    public function __construct($parent = '') {
        $this->pcmd  = $parent;
        $this->color = new Colors();
        @chdir(APPROOT);
    }

    public function setParent($parent) {
        $this->pcmd = $parent;
    }

    public function help($message = '') {
        $color = $this->color;
        if ($message) {
            echo "\n" . $this->color->str(wordwrap(ucfirst($message), 80, "\n  "), 'red') . "\n\n";
        } else {
            echo "\n", wordwrap($this->desc(), 80, "\n  ") . "\n\n";
        }
        if (!$this->subCmds) {
            $this->subCommands();
        }
        $maxlength = strlen('-h, --help');
        if ($this->subCmds) {
            $cmds = array_keys($this->subCmds);
            array_walk($cmds, function ($value) use (&$maxlength) {
                $len = strlen($value);
                if ($len > $maxlength) $maxlength = $len;
            });
            $leftPad = str_pad('', $maxlength + 7, ' ', STR_PAD_LEFT);
            $sMax    = 77 - $maxlength;
            echo $color->str("Usage:", 'green');
            echo " #php artisan " . $this->cmd() . " <command>\n\n";
            echo "Options:\n";
            echo '  ', str_pad('-h, --help', $maxlength + 5, ' ', STR_PAD_RIGHT), "display this help message\n";
            echo '  ', str_pad('-v', $maxlength + 5, ' ', STR_PAD_RIGHT), "display wulaphp version\n\n";
            echo "Commands:\n";
            /** @var ArtisanCommand $cmd 命令实例 */
            foreach ($this->subCmds as $name => $cmd) {
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
            $cmd = $this->cmd();
            echo "\nRun '#php artisan $cmd <command> --help' for more information on a command";
        } else {
            $opts  = $this->getOpts();
            $lopts = $this->getLongOpts();
            echo $color->str("Usage:", 'green');
            echo " #php artisan ", ($this->pcmd ? $this->pcmd . ' ' : ''), $this->cmd(), (($opts || $lopts) ? ' [options] ' : ' '), $color->str($this->argDesc(), 'blue'), "\n\n";
            $args = [];
            foreach ($opts as $opt => $msg) {
                $opss        = explode(':', $opt);
                $l           = count($opss);
                $arg         = $opss[ $l - 1 ];
                $op          = '-' . $opss[0] . ($arg && $l == 2 ? " <$arg>" : ($arg && $l == 3 ? " [$arg]" : ''));
                $args[ $op ] = $msg;
                $len         = strlen($op);
                if ($len > $maxlength) $maxlength = $len;
            }

            foreach ($lopts as $opt => $msg) {
                $opss        = explode(':', $opt);
                $l           = count($opss);
                $arg         = $opss[ $l - 1 ];
                $op          = '--' . $opss[0] . ($arg && $l == 2 ? " <$arg>" : ($arg && $l == 3 ? " [$arg]" : ''));
                $args[ $op ] = $msg;
                $len         = strlen($op);
                if ($len > $maxlength) $maxlength = $len;
            }
            $leftPad = str_pad('', $maxlength + 7, ' ', STR_PAD_LEFT);
            $sMax    = 77 - $maxlength;
            if ($args) {
                echo "Options:\n";
            }
            foreach ($args as $name => $desc) {
                echo "  ", $color->str(str_pad($name, $maxlength + 5, ' ', STR_PAD_RIGHT), 'green');
                $line = substr($desc, 0, $sMax);
                echo $line;
                $i = 1;
                while ($line = substr($desc, $sMax * $i, $sMax)) {
                    echo "\n", $leftPad, $line;
                    $i++;
                }
                echo "\n";
            }
        }
        echo "\n";
        if ($message) {
            exit (1);
        } else {
            exit (0);
        }
    }

    protected function getOpts() {
        return [];
    }

    protected function getLongOpts() {
        return [];
    }

    protected final function getOptions() {
        static $options = null;
        global $argv, $argc;
        if ($options !== null) {
            return $options;
        }
        $options   = [];
        $op        = [];
        $opts['h'] = '';
        $opts      = array_merge($opts, (array)$this->getOpts());

        foreach ($opts as $opt => $msg) {
            $opss                 = explode(':', $opt);
            $l                    = count($opss);
            $op[ '-' . $opss[0] ] = $l;
        }
        $opts = ['help' => ''];
        $opts = array_merge($opts, (array)$this->getLongOpts());

        foreach ($opts as $opt => $msg) {
            $opss                  = explode(':', $opt);
            $l                     = 10 + count($opss);
            $op[ '--' . $opss[0] ] = $l;
        }

        foreach ($op as $o => $r) {
            // $r [1,11] => 标识参数; [2,12]=> 必填选项; [3,13] => 可选参数
            $key = trim($o, '-');
            for ($i = 2; $i < $argc; $i++) {
                if (strpos($argv[ $i ], $o) === 0) {//提供参数(选项)
                    $ov         = $argv[ $i ];
                    $argv[ $i ] = null;
                    if ($r % 10 == 1) {
                        $options[ $key ] = true;
                        break;
                    }
                    $options[ $key ] = null;
                    $v               = str_replace($o, '', $ov);
                    if ($v || is_numeric($v)) {
                        if ($r < 10) {
                            $options[ $key ] = $v;
                            break;
                        } else if ($r > 10) {
                            $this->help('Invalid option "' . trim($ov, '-') . '"');
                        }
                    }
                    //查找参数值
                    for ($j = $i + 1; $j < $argc; $j++) {
                        $v = $argv[ $j ];
                        if ($v == '=') {
                            $argv[ $j ] = null;
                            continue;
                        } else if (strpos('-', $v) === 0) {
                            break;
                        } else {
                            $argv[ $j ]      = null;
                            $options[ $key ] = $v;
                            break;
                        }
                    }
                    //可选参数出现但未提供值
                    if ($r % 10 == 3 && (!$v && !is_numeric($v))) {
                        $this->help('Invalid option "' . $o . '"');
                    }
                }
            }
            //必选参数检查
            if ($r % 10 == 2 && !isset($options[ $key ])) {
                if (key_exists($key, $options)) {
                    $this->help('Invalid option "' . $o . '"');
                } else if (!isset($options['h']) && !isset($options['help'])) {
                    $this->help('Missing option "' . $o . '"');
                }
                break;
            }
        }
        $this->argv[0] = $argv[0];
        $this->argv[1] = $argv[1];
        for ($i = 2; $i < $argc; $i++) {
            if ($argv[ $i ] && preg_match('#^(-([a-z\d])|--([a-z\d\-_]+))$#i', $argv[ $i ], $ms)) {
                $this->help('Invalid option "' . $ms[0] . '"');
            }
            if (!is_null($argv[ $i ])) {
                $this->argv[] = $argv[ $i ];
            }
        }
        $this->arvc = count($this->argv);

        return $options;
    }

    protected final function opt($index = -1, $default = '') {
        $argvv = $this->argv;
        $argcc = $this->arvc;
        if ($index < 0) {
            $index = $argcc + $index;
            if ($index < 2) {
                return $default;
            }
        } else {
            $index += 2;
        }

        if ($argcc > 2 && isset($argvv[ $index ])) {
            return $argvv[ $index ];
        }

        return $default;
    }

    protected final function log($message = '', $nl = true) {
        $msg = $message . ($nl ? "\n" : '');
        echo $msg;
        flush();
    }

    protected final function logd($message = '', $nl = true) {
        if (DEBUG < DEBUG_INFO) {
            $msg = ($nl ? '[' . date('Y-m-d H:i:s') . '] ' . $this->pid . ' [DEBUG] ' : '') . $message . ($nl ? "\n" : '');
            echo $msg;
            flush();
        }
    }

    protected final function loge($message = '', $nl = true) {
        if (DEBUG < DEBUG_OFF) {
            $msg = ($nl ? '[' . date('Y-m-d H:i:s') . '] ' . $this->pid . ' [ERROR] ' : '') . $message . ($nl ? "\n" : '');
            echo $msg;
            flush();
        }
    }

    protected final function logw($message = '', $nl = true) {
        if (DEBUG < DEBUG_ERROR) {
            $msg = ($nl ? '[' . date('Y-m-d H:i:s') . '] ' . $this->pid . ' [WARN] ' : '') . $message . ($nl ? "\n" : '');
            echo $msg;
            flush();
        }
    }

    protected final function logi($message = '', $nl = true) {
        if (DEBUG < DEBUG_WARN) {
            $msg = ($nl ? '[' . date('Y-m-d H:i:s') . '] ' . $this->pid . ' [INFO] ' : '') . $message . ($nl ? "\n" : '');
            echo $msg;
            flush();
        }
    }

    protected final function error($message) {
        $color = $this->color;
        $msg   = $this->pid . $color->str("Error:\n", 'red') . $message . "\n";
        echo $msg;

        flush();
    }

    protected final function output($message, $rtn = true) {
        echo $message, $rtn ? "\n" : '';

        flush();
    }

    protected final function cell($messages, $len = 0, $pad = ' ') {
        if (!is_array($messages)) {
            $messages = [[$messages, $len]];
        }
        $msgs = [];
        foreach ($messages as $message) {
            $l = strlen($message[0]);
            if ($l < $message[1]) {
                $msgs[] = str_pad($message[0], $message[1], $pad);
            } else if ($l > $message[1]) {
                $msgs[] = mb_substr($message[0], 0, $message[1]);
            } else {
                $msgs[] = $message[0];
            }
        }

        return implode('', $msgs);
    }

    protected final function success($message) {
        $color = $this->color;
        $msg   = $this->pid . $color->str("SUCCESS:\n", 'green') . $message . "\n";
        echo $msg;
        flush();
    }

    protected final function redText($message) {
        return $this->color->str($message, 'red');
    }

    protected final function greenText($message) {
        return $this->color->str($message, 'green');
    }

    protected final function blueText($message) {
        return $this->color->str($message, 'blue');
    }

    public function run() {
        global $argv, $argc;
        $options = null;
        if (!$this->subCmds) {
            $this->subCommands();
        }
        if ($this->subCmds) {
            $cmd = null;
            for ($i = 2; $i < $argc; $i++) {
                $_cmd = $argv[ $i ];
                if ($_cmd{0} != '-') {
                    $cmd = $_cmd;
                    break;
                }
            }
            if (!$cmd) {
                if ($argc > 2) {
                    $options = $this->getOptions();
                } else {
                    $this->help();
                }
            } else if ($cmd && !isset($this->subCmds[ $cmd ])) {
                $this->help('Unkown command: ' . $cmd);
            } else {
                /**@var \wulaphp\artisan\ArtisanCommand */
                $cmd = $this->subCmds[ $cmd ];

                return $cmd->run();
            }
        }
        if ($options == null) {
            $options = $this->getOptions();
        }
        if (isset($options['h']) || isset($options['help'])) {
            $this->help();
        }
        $argOk = $this->argValid($options) && $this->paramValid($options);
        if (!$argOk) {
            exit(1);
        }

        return $this->execute($options);
    }

    protected function argDesc() {
        return '';
    }

    /**
     * 校验参数.
     *
     * @param array $options
     *
     * @return bool
     */
    protected function argValid(/** @noinspection PhpUnusedParameterInspection */
        $options) {
        return true;
    }

    /**
     * 校验param
     *
     * @param array $options
     *
     * @return bool
     */
    protected function paramValid(/** @noinspection PhpUnusedParameterInspection */
        $options) {
        return true;
    }

    /**
     * 子命令
     */
    protected function subCommands() {

    }

    public abstract function cmd();

    public abstract function desc();

    protected abstract function execute($options);
}