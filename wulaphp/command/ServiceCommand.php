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
	private function start(string $service) {
		if ($service) {
			$rtn = $this->sendCommand('start', ['service' => $service]);
			$this->output($rtn);
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
	private function stop(string $service, bool $restart = false) {
		$rtn = $this->sendCommand('stop', ['service' => $service, 'restart' => $restart]);

		$this->output($rtn);
	}

	/**
	 * 重新加载配置
	 *
	 * @param string $service
	 */
	private function reload(string $service) {
		$rtn = $this->sendCommand('reload', ['service' => $service]);

		$this->output($rtn);
	}

	/**
	 * 查看进程信息
	 *
	 * @param string $service
	 */
	private function ps(string $service) {
		$rtn = $this->sendCommand('ps', ['service' => $service]);

		$this->output($rtn);
	}

	/**
	 * 状态
	 *
	 * @param string $service
	 */
	private function status(string $service) {
		$rtn = $this->sendCommand('status', ['service' => $service]);

		$this->output($rtn);
	}

	/**
	 * 发送管理命令
	 *
	 * @param string $command
	 * @param array  $args
	 *
	 * @return mixed
	 */
	private function sendCommand(string $command, array $args = []) {
		$data['command'] = $command;
		$data['args']    = $args;
		$payload         = json_encode($data) . "\r\n\r\n";
		$config          = App::config('service', true);
		$bind            = $config->get('bind', 'unix://' . TMP_PATH . 'service.sock');
		$binds           = explode(':', $bind);
		if ($binds[0] == 'unix') {
			$sock = @socket_create(AF_UNIX, SOCK_STREAM, SOL_TCP);
			if (!$sock) {
				$this->output($this->color->str(socket_strerror(socket_last_error()), 'red'));
				exit(-1);
			}
			$rtn = @socket_connect($sock, substr($bind, 7));
		} else {
			$addr = $binds[0] ?? '127.0.0.1';
			$port = $binds[1] ?? '5858';
			$sock = @socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
			if (!$sock) {
				$this->output($this->color->str(socket_strerror(socket_last_error()), 'red'));
				exit(-1);
			}
			@socket_set_option($sock, SOL_SOCKET, SO_REUSEADDR, 1);
			$rtn = @socket_connect($sock, $addr, $port);
		}

		if (!$rtn) {
			$this->output($this->color->str(socket_strerror(socket_last_error()), 'red'));
			exit(-1);
		}

		$rtn = @socket_write($sock, $payload, strlen($payload));
		if (!$rtn) {
			$this->output($this->color->str(socket_strerror(socket_last_error()), 'red'));
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
				$this->output($this->color->str(socket_strerror(socket_last_error()), 'red'));
				exit(-1);
			}
		}

		return explode("\r\n\r\n", $msgs)[0];
	}
}



