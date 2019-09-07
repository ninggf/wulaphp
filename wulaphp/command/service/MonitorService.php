<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace wulaphp\command\service;

use wulaphp\conf\ConfigurationLoader;

/**
 * 监控服务.
 *
 * @package wulaphp\command\service
 */
class MonitorService extends Service {
    private $sock;
    private $clients  = [];
    private $changed  = [];
    private $msgs     = [];
    private $services = [];
    private $pids     = [];
    private $sockFile;
    private $cfgFile;

    public function __construct($name, array $config) {
        parent::__construct($name, $config);
        $this->logFile = LOGS_PATH . 'service.log';
        $this->setVerbose($this->getOption('verbose', 'vvv'));
        $this->output('Starting', false);
        $this->cfgFile = TMP_PATH . '.service.json';
        //第一步修改当前进程uid
        $user = $this->getOption('user');
        if ($user) {
            $uinfo = @posix_getpwnam($user);
            if ($uinfo) {
                if (!@posix_setuid($uinfo['uid'])) {
                    $this->output('cannot run service command by user ' . $user);
                    exit(-1);
                }
            } else {
                $this->output('user ' . $user . ' is not found');
                exit(-1);
            }
            $group = $this->getOption('group');
            if ($group) {
                $ginfo = @posix_getgrnam($group);
                if ($ginfo) {
                    $gid = $ginfo['gid'];
                } else {
                    $this->output('group ' . $group . ' is not found');
                    exit(-1);
                }
            } else {
                $gid = $uinfo['gid'];
            }
            if ($gid) {
                if (!@posix_setgid($gid)) {
                    $this->output('cannot run service command by group ' . $gid);
                    exit(-1);
                }
            }
        }
        $this->output('.', false);
        //第二步启动管理socket
        $this->initSocket();
        $this->output('.', false);
        //第三步解析配置
        $this->reloadConfig($config);
        //第四步安装信号
        $this->initSignal();
        $this->output('.', false);
        $this->output($this->color->str('Done', 'green'));
    }

    public function __destruct() {
        if ($this->sock) {
            @socket_close($this->sock);
        }
        if ($this->clients) {
            foreach ($this->clients as $c) {
                @socket_close($c);
            }
        }
    }

    /**
     * 运行
     */
    public function run() {
        @fclose(STDIN);
        @fclose(STDOUT);
        @fclose(STDERR);
        $this->logi('started');
        while (!$this->shutdown) {
            $this->checkServices();
            $this->select();
            if ($this->changed && !$this->shutdown) {
                $this->accept();
                $this->recieved();
            }
        }
        $this->logi('stopping ...');
        if ($this->clients) {//关链接
            foreach ($this->clients as $c) {
                @socket_close($c);
            }
            $this->clients = [];
            $this->logd('close connections done');
        }
        if ($this->sock) {//关sock
            @socket_close($this->sock);
            $this->sock = null;
            $this->logd('close socket done');
        }
        if ($this->rSignal) {//收到信号了，将信号转发给子进程
            $wks = $this->pids;
            if ($wks) {
                foreach ($wks as $pid => $s) {
                    @posix_kill($pid, $this->rSignal);
                    @pcntl_signal_dispatch();
                }
                $this->logd('send kill signal done');
            }
        }
        while (count($this->pids) > 0) {
            $pid = pcntl_wait($status, WNOHANG);
            if ($pid > 0) {//有进程退出啦
                $sid = isset($this->pids[ $pid ]) ? $this->pids[ $pid ] : '';
                unset($this->pids[ $pid ]);
                $this->logd('service ' . $sid . ', pid ' . $pid . ' exit');
                if ($sid && isset($this->services[ $sid ])) {
                    unset($this->services[ $sid ]['pids'][ $pid ]);
                }
            }
            usleep(100);
        }
        if ($this->sockFile) {
            //@unlink($this->sockFile);
        }
        $this->logi('stopped');
    }

    /**
     * 处理管理消息
     *
     * @param string   $msg
     * @param resource $socket
     */
    private function onMessage($msg, $socket) {
        try {
            $payload = @json_decode($msg, true);
            if (!$payload) {
                $this->response($socket, ['error' => 403, 'message' => 'error request']);

                return;
            }
            $command = isset($payload['command']) ? $payload['command'] : 'status';
            $service = isset($payload['args']['service']) ? $payload['args']['service'] : '';
            switch ($command) {
                case 'stop':
                    if ($service) {
                        $rst = $this->stopService($service);
                        $this->response($socket, ['status' => $rst ? 'done' : 'fail']);
                    } else {
                        $this->response($socket, ['status' => 'done']);
                        $this->shutdown = true;
                        $this->rSignal  = SIGTERM;
                    }
                    break;
                case 'start':
                    if ($service) {
                        $rst = $this->startService($service);
                        $this->response($socket, ['status' => $rst ? 'done' : 'fail']);
                    } else {
                        $this->response($socket, ['status' => 'fail']);
                    }
                    break;
                case 'reload':
                    $rst = $this->reloadConfig(null, $service);
                    $this->response($socket, ['status' => $rst ? 'done' : 'fail']);
                    break;
                case 'ps':
                    if ($service) {
                        if (isset($this->services[ $service ])) {
                            $pids = isset($this->services[ $service ]['pids']) ? $this->services[ $service ]['pids'] : [];
                            $this->response($socket, [
                                'ps'   => [$service => $pids],
                                'ssid' => posix_getpid()
                            ]);
                        } else {
                            $this->response($socket, ['ps' => [], 'ssid' => posix_getpid()]);
                        }
                    } else {
                        $ps = [];
                        foreach ($this->services as $s => $ser) {
                            $ps[ $s ] = isset($ser['pids']) ? $ser['pids'] : [];
                        }
                        $this->response($socket, ['ps' => $ps, 'ssid' => posix_getpid()]);
                    }
                    break;
                case 'status':
                default:
                    if ($service) {
                        if (isset($this->services[ $service ])) {
                            $this->response($socket, [
                                'status' => $this->services[ $service ]['status'],
                                'detail' => $this->services[ $service ]
                            ]);
                        } else {
                            $this->response($socket, ['status' => 'unknown', 'detail' => []]);
                        }
                    } else {
                        $this->response($socket, ['status' => 'running', 'services' => $this->services]);
                    }
            }
        } catch (\Exception $e) {
            $this->response($socket, ['error' => 500, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * 监测服务
     */
    private function checkServices() {
        do {
            $pid = @pcntl_wait($status, WNOHANG);
            if ($pid > 0) {//有进程退出啦
                $sid = isset($this->pids[ $pid ]) ? $this->pids[ $pid ] : '';
                unset($this->pids[ $pid ]);
                if ($sid && isset($this->services[ $sid ])) {
                    if (@pcntl_wifexited($status)) {
                        $rtn = @pcntl_wexitstatus($status);
                        $this->logd('service ' . $sid . ', pid ' . $pid . ' exits with code: ' . $rtn);
                        if ($rtn == 1) {//子进程出错了
                            $this->services[ $sid ]['status'] = 'error';
                            $this->services[ $sid ]['msg']    = 'process exit 1';
                        }
                    } else if (@pcntl_wifsignaled($status)) {
                        $this->logd('service ' . $sid . ', pid ' . $pid . ' exits with code: -1');
                        $this->services[ $sid ]['status'] = 'error';
                        $this->services[ $sid ]['msg']    = 'process exit -1';
                    } else {
                        $this->logd('service ' . $sid . ', pid ' . $pid . ' exits with code: -2');
                    }
                    unset($this->services[ $sid ]['pids'][ $pid ]);
                } else {
                    $this->logw('unkown service ' . $sid . ', pid ' . $pid);
                }
            } else {
                break;
            }
        } while ($pid > 0);

        $removed = [];
        foreach ($this->services as $id => $service) {
            $status = $service['status'];
            if (!isset($this->services[ $id ]['pids'])) {
                $this->services[ $id ]['pids'] = [];
            }
            if ($this->services[ $id ]['status'] != 'error') {
                unset($this->services[ $id ]['msg']);
            }
            switch ($status) {
                case 'new':
                case 'starting':
                case 'reloading':
                case 'running':
                    //补齐进程
                    $service['worker'] = intval(isset($service['worker']) ? $service['worker'] : 1);
                    $serOk             = $this->checkSer($service, $msg);
                    if ($serOk) {
                        $forkOk = true;
                        while (count($this->services[ $id ]['pids']) < $service['worker']) {
                            $pid = @pcntl_fork();
                            if (0 === $pid) {//服务进程
                                $serImpl = $this->getSerImpl($id, $service);
                                try {
                                    $rtn = $serImpl->run();//极有可能是死循环
                                } catch (\Exception $e) {
                                    $this->loge('[' . $id . '] ' . $e->getMessage());
                                    $rtn = false;
                                }
                                //服务进程肯定要退出
                                if ($rtn === false) {
                                    exit(1);
                                } else {
                                    exit(0);
                                }
                            } else if ($pid > 0) {
                                //监控进程
                                $this->pids[ $pid ]                    = $id;
                                $this->services[ $id ]['pids'][ $pid ] = 1;
                                $this->logd('service ' . $id . ', pid ' . $pid . ' created');
                            } else {
                                $forkOk = false;
                                break;
                            }
                        }
                        if ($status != 'running' && $forkOk) {
                            $this->services[ $id ]['status'] = 'running';
                        } else if (!$forkOk) {
                            $this->services[ $id ]['status'] = 'error';
                        }
                    } else {
                        $this->services[ $id ]['status'] = 'error';
                        $this->services[ $id ]['msg']    = $msg;
                    }
                    break;
                case 'error':
                    break;
                case 'disabled':
                    //如果还有进程干死他们
                    if (isset($service['pids'])) {
                        foreach ($service['pids'] as $pid => $v) {
                            @posix_kill($pid, SIGTERM);
                            @pcntl_signal_dispatch();
                        }
                    }
                    break;
                case 'reload':
                case 'remove':
                case 'stopping':
                case 'stop':
                default:
                    //如果还有进程干死他们
                    if (isset($service['pids'])) {
                        foreach ($service['pids'] as $pid => $v) {
                            @posix_kill($pid, SIGTERM);
                            @pcntl_signal_dispatch();
                        }
                    }
                    if ($status == 'reload') {
                        $this->services[ $id ]['status'] = 'reloading';
                    } else if ($status == 'remove') {
                        $this->services[ $id ]['status'] = 'removed';
                        $removed[]                       = $id;
                    } else {
                        $this->services[ $id ]['status'] = 'stop';
                    }
            }
        }
        if ($removed) {
            foreach ($removed as $id) {
                unset($this->services[ $id ]);
            }
        }
    }

    /**
     * 重新加载配置
     *
     * @param array|null $config
     * @param string     $service
     *
     * @return bool
     */
    private function reloadConfig($config = null, $service = '') {
        if (!$config) {
            $config       = $this->loadRuntimeCfg();
            $this->config = $config;
            $this->setVerbose($this->getOption('verbose'));
        }
        if ($config) {
            $services = isset($config['services']) ? $config['services'] : [];
            if ($service) {
                if (isset($services[ $service ]) && isset($this->services[ $service ])) {
                    $this->services[ $service ] = array_merge($this->services[ $service ], $services[ $service ]);
                    $status                     = isset($services[ $service ]['status']) ? $services[ $service ]['status'] : '';
                    if (!$status && $this->services[ $service ]['status'] == 'running') {
                        $this->services[ $service ]['status'] = 'reload';
                    }

                    return true;
                }

                return false;
            }
            foreach ($services as $s => $conf) {
                if (!isset($this->services[ $s ])) {
                    $this->services[ $s ] = $conf;
                    if ($conf['status'] != 'disabled') {
                        $this->services[ $s ]['status'] = 'new';
                    }
                } else {
                    $this->services[ $s ] = array_merge($this->services[ $s ], $conf);
                    $status               = isset($conf['status']) ? $conf['status'] : '';
                    if (!$status) {
                        if ($status == 'disabled') {
                            $this->services[ $s ]['status'] = 'disabled';
                        } else if ($this->services[ $s ]['status'] == 'running') {
                            $this->services[ $s ]['status'] = 'reload';
                        }
                    }
                }
            }
            $sids = array_keys($this->services);
            if ($sids) {
                foreach ($sids as $sid) {
                    if (!isset($services[ $sid ])) {
                        $this->services[ $sid ]['status'] = 'remove';
                    }
                }
            }

            return true;
        }

        return false;
    }

    private function stopService($service) {
        if ($service) {
            if (isset($this->services[ $service ])) {
                $this->services[ $service ]['status'] = 'stopping';

                return true;
            }

            return false;
        }

        return false;
    }

    private function startService($service) {
        if ($service) {
            $config       = $this->loadRuntimeCfg();
            $this->config = $config;
            $services     = isset($config['services']) ? $config['services'] : [];
            if (isset($services[ $service ])) {
                $status = isset($services[ $service ]['status']) ? $services[ $service ]['status'] : '';
                if ($status == 'disabled') return false;

                if (isset($this->services[ $service ])) {
                    $this->services[ $service ] = array_merge($this->services[ $service ], $services[ $service ]);
                } else {
                    $this->services[ $service ] = $services[ $service ];
                }
                $this->services[ $service ]['status'] = 'starting';

                return true;
            }
        }

        return false;
    }

    /**
     * 初始化管理端口
     */
    private function initSocket() {
        $fail  = $this->color->str('Fail', 'red');
        $bind  = $this->getOption('bind', 'unix:' . TMP_PATH . 'service.sock');
        $binds = explode(':', $bind);
        if ($binds[0] == 'unix') {
            $sock = @socket_create(AF_UNIX, SOCK_STREAM, 0);
            if (!$sock) {
                $this->output($fail . "\nCannot create administrator socket:" . socket_strerror(socket_last_error()));
                exit(-1);
            }
            $sockFile = substr($bind, 5);
            if (!$sockFile) {
                $sockFile = TMP_PATH . 'service.sock';
            }
            $this->sockFile = $sockFile;
            if ($this->sockFile) {
                $rtn = @socket_bind($sock, $this->sockFile);
            } else {
                @socket_close($sock);
                $this->output($fail . "\n" . 'sock file is empty!');
                exit(-1);
            }
        } else {
            $addr = isset($binds[0]) ? $binds[0] : '127.0.0.1';
            $port = isset($binds[1]) ? $binds[1] : '5858';
            $sock = @socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
            if (!$sock) {
                $this->output($fail . "\nCannot create administrator socket:" . socket_strerror(socket_last_error()));
                exit(-1);
            }
            @socket_set_option($sock, SOL_SOCKET, SO_REUSEADDR, 1);
            $rtn = @socket_bind($sock, $addr, $port);
        }
        if (!$rtn) {
            @socket_close($sock);
            $this->output($fail . "\n" . socket_strerror(socket_last_error()));
            exit(-1);
        }
        $rst = @socket_listen($sock);
        if (!$rst) {
            @socket_close($sock);
            $this->output($fail . "\n" . socket_strerror(socket_last_error()));
            exit(-1);
        }
        $this->sock = $sock;
    }

    /**
     * 选出有变化的socket
     */
    private function select() {
        $this->changed = array_merge([$this->sock], array_values($this->clients));
        $null          = null;
        @socket_select($this->changed, $null, $null, 1);
    }

    /**
     * 处理新链接
     */
    private function accept() {
        if (!in_array($this->sock, $this->changed)) {
            return;
        }
        if ($this->shutdown) {
            return;
        }
        $socket_new = @socket_accept($this->sock); //accept new socket

        if ($socket_new) {
            $this->clients[ uniqid() ] = $socket_new;
        }

        $key = array_search($this->sock, $this->changed);

        if ($key !== false) {
            unset($this->changed[ $key ]);
        }
    }

    /**
     * 读取数据
     */
    private function recieved() {
        if ($this->shutdown) {
            return;
        }
        foreach ($this->changed as $key => $socket) {
            $socketId = array_search($socket, $this->clients);
            if (!$socketId) continue;
            $buffer = @socket_read($socket, 2048, PHP_BINARY_READ);
            if (!$buffer) {//出错啦,断开链接
                @socket_close($socket);
                unset($this->clients[ $socketId ], $this->msgs[ $socketId ]);
            } else if ($buffer) {
                $this->msgs[ $socketId ][] = $buffer;
                $this->unpackMsg($socketId, $socket);
            }
        }
    }

    private function checkSer(array $config, &$msg = null) {
        $type = isset($config['type']) ? $config['type'] : 'parallel';
        if (!$type) {
            $type = 'parallel';
        }
        if ($type == 'script') {
            $type = 'parallel';
        } else if ($type == 'gearman' && !extension_loaded('gearman')) {
            $msg = 'gearman extension not found';
            $this->logw($msg);

            return false;
        }

        $typeCls = 'wulaphp\command\service\\' . ucfirst($type) . 'Service';
        if (!class_exists($typeCls)) {
            $msg = 'unkown service type: ' . $type;
            $this->logw($msg);

            return false;
        }

        return true;
    }

    private function getSerImpl($id, array $config) {
        $type = isset($config['type']) ? $config['type'] : 'parallel';
        if (!$type) {
            $type = 'parallel';
        }
        if ($type == 'script') {
            $type = 'parallel';
        }
        $typeCls = 'wulaphp\command\service\\' . ucfirst($type) . 'Service';
        if (!class_exists($typeCls)) {
            $this->logw('unkown service type: ' . $type);

            return null;
        }
        /**@var \wulaphp\command\service\Service $typeClz */
        $typeClz = new $typeCls($id, $config);
        $typeClz->initSignal();//安装信号量以便可以优雅地退出
        $typeClz->setVerbose($this->verbose);

        return $typeClz;
    }

    /**
     * 解包消息.
     *
     * @param string $socketId
     * @param        $socket
     */
    private function unpackMsg($socketId, $socket) {
        $msgs = $this->msgs[ $socketId ];
        if ($msgs) {
            $msgs   = implode('', $msgs);
            $chunks = explode("\r\n\r\n", $msgs);
            if (count($chunks) == 1) {
                $this->msgs[ $socketId ] = [$msgs];

                return;
            }
            $popAll = $chunks[ count($chunks) - 1 ] == '';
            if ($popAll) {
                $this->msgs[ $socketId ] = [];
            } else {
                $chunk                   = array_pop($chunks);
                $this->msgs[ $socketId ] = [$chunk];
            }
            foreach ($chunks as $chunk) {
                if ($chunk) {
                    $this->onMessage($chunk, $socket);
                }
            }
        }
    }

    /**
     * 响应.
     *
     * @param resource $socket
     * @param array    $data
     */
    private function response($socket, array $data) {
        $msg = json_encode($data, JSON_UNESCAPED_SLASHES) . "\r\n\r\n";
        @socket_write($socket, $msg, strlen($msg));
    }

    /**
     * @return array
     */
    private function loadRuntimeCfg() {
        $loader = new ConfigurationLoader();
        $config = $loader->loadConfig('service')->toArray();
        if (is_file($this->cfgFile)) {
            $cfg = @file_get_contents($this->cfgFile);
            $cfg = $cfg ? @json_decode($cfg, true) : null;
            if ($cfg) {
                $config = array_merge($config, $cfg);
            }
        }

        return $config;
    }
}