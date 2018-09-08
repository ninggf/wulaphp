<?php
declare(ticks=1);
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace wulaphp\command;

use wulaphp\app\App;
use wulaphp\artisan\ArtisanCommand;
use wulaphp\command\service\MonitorService;

/**
 * 服务命令，让服务优雅地运行在后台.
 * @package wulaphp\command
 */
class ServiceCommand extends ArtisanCommand {
    public function __construct() {
        parent::__construct();
        define('ARTISAN_TASK_PID', 1);
        set_time_limit(0);
    }

    public function cmd() {
        return 'service';
    }

    public function desc() {
        return 'service in background';
    }

    public function argDesc() {
        return '<start|stop|status|restart|reload|ps> [service]';
    }

    protected function execute($options) {
        if (!function_exists('pcntl_fork')) {
            $this->error('miss pcntl extension, install it first!');
            exit(1);
        }
        if (!function_exists('posix_getpid')) {
            $this->error('miss posix extension, install it first!');
            exit(1);
        }
        if (!function_exists('socket_create')) {
            $this->error('miss sockets extension, install it first!');
            exit(1);
        }
        $cmd = $this->opt(0);
        if (empty($cmd)) {
            $cmd = 'help';
        }
        $service = $this->opt(1);
        $cmd     = strtolower($cmd);
        if ($cmd == 'help') {
            $this->help();
            exit(0);
        }

        if (!in_array($cmd, ['start', 'status', 'stop', 'reload', 'restart', 'ps'])) {
            $this->error('unkown command: ' . $this->color->str($cmd, 'red'));
            exit(1);
        }

        switch ($cmd) {
            case 'start':
                $this->start($service);
                break;
            case 'stop':
                $this->stop($service);
                break;
            case 'reload':
                $this->reload($service);
                break;
            case 'ps':
                $this->ps($service);
                break;
            case 'restart':
                $this->stop($service, true);
                break;
            default:
                $this->status($service);
        }
    }

    /**
     * 启动
     *
     * @param string $service
     */
    private function start($service) {
        if ($service) {
            $this->output('Starting ...', false);
            $rtn = $this->sendCommand('start', ['service' => $service]);
            if ($rtn) {
                $this->output($this->getStatus($rtn['status']));
            }
        } else {
            //启动service monitor process
            $pid = @pcntl_fork();
            if ($pid > 0) {//主程序退出
                exit(0);
            } else if (0 === $pid) {//子进程
                try {
                    umask(0);
                    $sid = @posix_setsid();
                    if ($sid < 0) {
                        $this->error('[service] could not detach session id.');
                        exit(1);
                    }
                    $monitor = new MonitorService('monitor', App::config('service', true)->toArray());
                    $monitor->run();
                } catch (\Exception $e) {
                    exit(-1);
                }
            } else {//fork 失败
                $this->error('cannot create process');
                exit(-1);
            }
        }
    }

    /**
     * 停止
     *
     * @param string $service
     * @param bool   $restart
     */
    private function stop( $service,  $restart = false) {
        $this->output('Stopping ...', false);
        $rtn = $this->sendCommand('stop', ['service' => $service, 'restart' => $restart]);
        if ($rtn) {
            $this->output($this->getStatus($rtn['status']));
        }
    }

    /**
     * 重新加载配置
     *
     * @param string $service
     */
    private function reload($service) {
        $this->output('Reloading ...', false);
        $rtn = $this->sendCommand('reload', ['service' => $service]);

        if ($rtn) {
            $this->output($this->getStatus($rtn['status']));
        }
    }

    /**
     * 查看进程信息
     *
     * @param string $service
     */
    private function ps($service) {
        $rtn = $this->sendCommand('ps', ['service' => $service]);
        if ($rtn) {

            $this->output('monitor');
            $pcnt = count($rtn['ps']);
            $this->output(($pcnt ? '├── ' : '└── ') . $this->color->str($rtn['ssid'], 'green'));
            foreach ($rtn['ps'] as $s => $ids) {
                $pcnt--;
                if ($ids) {
                    $this->output(($pcnt ? '├── ' : '└── ') . $s);
                    $pids = array_keys($ids);
                    $cnt  = count($pids);
                    for ($i = 0; $i < $cnt - 1; $i++) {
                        $this->output(($pcnt ? '│   ├── ' : '    ├── ') . $pids[ $i ]);
                    }
                    $this->output(($pcnt ? '│   └── ' : '    └── ') . $pids[ $cnt - 1 ]);
                }
            }
        }
    }

    /**
     * 状态
     *
     * @param string $service
     */
    private function status( $service) {
        $rtn = $this->sendCommand('status', ['service' => $service]);
        if ($rtn) {
            $status = $this->getStatus($rtn['status']);
            if ($service) {
                if (isset($rtn['detail']) && $rtn['detail']) {
                    foreach ($rtn['detail'] as $item => $v) {
                        $this->output($this->cell($item, 20), false);
                        if ($item == 'pids') {
                            $this->output(implode(',', array_keys($v)));
                        } else if ($item == 'status') {
                            $this->output($this->getStatus($v));
                        } else if ($item == 'env') {
                            $this->output($this->cell([['key', 20], ['value', 6]]));
                            foreach ($v as $vk => $vv) {
                                $this->output($this->cell([['', 20], [$vk, 20]]) . $vv);
                            }
                        } else if (is_array($v)) {
                            $this->output(json_encode($v));
                        } else {
                            $this->output($v);
                        }
                    }
                } else {
                    $this->output($service . ' is ' . $status);
                }
            } else {
                $services = $rtn['services'];
                $this->output($this->cell([
                    ['Service', 20],
                    ['Type', 16],
                    ['Worker', 10],
                    ['Status', 20],
                    ['Message', 44]
                ]));
                $this->output($this->cell('-', 120, '-'));
                foreach ($services as $id => $ser) {
                    $this->output($this->cell([
                        [$id, 20],
                        [$ser['type'], 16],
                        [isset($ser['worker']) ?$ser['worker']: 1, 10],
                        [$this->getStatus($ser['status']), 20],
                        [isset($ser['msg']) ?$ser['msg']: '', 44]
                    ]));
                }
            }
        }
    }

    private function getStatus($status) {
        switch ($status) {
            case 'running':
            case 'new':
            case 'starting':
            case 'reloading':
            case 'reload':
                $status = $this->color->str($status, 'green');
                break;
            case 'stop':
                $status = $this->color->str('stopped', 'yellow');
                break;
            case 'stopping':
                $status = $this->color->str('stopping', 'yellow');
                break;
            case 'error':
                $status = $this->color->str('error', 'red');
                break;
            case 'disabled':
                $status = $this->color->str('disabled', 'cyan');
                break;
            case 'done':
                $status = $this->color->str('Done', 'green');
                break;
            case 'fail':
                $status = $this->color->str('Fail', 'red');
                break;
            default:
                $status = $this->color->str($status, 'light_gray');
        }

        return $status;
    }

    /**
     * 发送管理命令
     *
     * @param string $command
     * @param array  $args
     *
     * @return mixed
     */
    private function sendCommand( $command, array $args = []) {
        $data['command'] = $command;
        $data['args']    = $args;
        $payload         = json_encode($data) . "\r\n\r\n";
        $config          = App::config('service', true);
        $bind            = $config->get('bind', 'unix:' . TMP_PATH . 'service.sock');
        $binds           = explode(':', $bind);
        $sockFile        = null;
        if ($binds[0] == 'unix') {
            $sock = @socket_create(AF_UNIX, SOCK_STREAM, 0);
            if (!$sock) {
                $this->output("\n" . $this->color->str(socket_strerror(socket_last_error()), 'red'));
                exit(-1);
            }
            $sockFile = substr($bind, 5);
            if (!$sockFile) {
                $sockFile = TMP_PATH . 'service.sock';
            }
            $rtn = @socket_connect($sock, $sockFile);
        } else {
            $addr = isset($binds[0]) ?$binds[0]: '127.0.0.1';
            $port = isset($binds[1]) ?$binds[1]: '5858';
            $sock = @socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
            if (!$sock) {
                $this->output("\n" . $this->color->str(socket_strerror(socket_last_error()), 'red'));
                exit(-1);
            }
            @socket_set_option($sock, SOL_SOCKET, SO_REUSEADDR, 1);
            $rtn = @socket_connect($sock, $addr, $port);
        }

        if (!$rtn) {
            $error_no = socket_last_error();
            if ($error_no == 2) {
                $this->output("\n" . $this->color->str('service is not running', 'red'));
            } else if ($error_no == 61) {
                $this->output("\n" . $this->color->str('service is not running', 'red'));
            } else {
                $this->output("\n" . $this->color->str(socket_strerror($error_no), 'red'));
            }
            exit(-1);
        }

        $rtn = @socket_write($sock, $payload, strlen($payload));
        if (!$rtn) {
            $this->output("\n" . $this->color->str(socket_strerror(socket_last_error()), 'red'));
            exit(-1);
        }
        $msgs = '';

        while (true) {
            $buffer = @socket_read($sock, 2048, PHP_BINARY_READ);
            if ($buffer) {
                $msgs .= $buffer;
                if (strpos($msgs, "\r\n\r\n") >= 0) {
                    @socket_close($sock);
                    break;
                }
            } else {
                $this->output("\n" . $this->color->str(socket_strerror(socket_last_error()), 'red'));
                exit(-1);
            }
        }

        $rst = explode("\r\n\r\n", $msgs)[0];
        $rst = @json_decode($rst, true);
        if ($rst && isset($rst['error']) && $rst['error']) {
            $this->output('');
            $this->error($rst['msg']);

            return null;
        } else {
            return $rst;
        }
    }
}



