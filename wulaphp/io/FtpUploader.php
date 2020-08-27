<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace wulaphp\io;

use wulaphp\app\App;

/**
 * Ftp 文件上传器。
 *
 * @package wulaphp\io
 */
class FtpUploader extends LocaleUploader {
    private $host;
    private $port;
    private $user;
    private $pwd;
    private $path    = '';
    private $timeout = 60;
    private $passive = true;
    private $homedir = '';
    private $ftp     = null;

    /**
     * FtpUploader constructor.
     *
     * @param string|null       $path     指定保存路径
     * @param string|null       $filename 指定保存文件名
     * @param string|array|null $config   FTP配置
     */
    public function __construct($path = null, $filename = null, $config = null) {
        parent::__construct(null, $filename);
        if ($config) {
            if (is_array($config)) {
                $opts = $config;
            } else {
                $opts = str_replace(["\n", "\r"], ['&', ''], trim($config));
            }
        } else {
            $opts = str_replace(["\n", "\r"], ['&', ''], trim(App::cfg('params@media')));
        }
        @parse_str($opts, $params);
        $path = aryget('path', $params, $path);
        if ($path) {
            $this->path = untrailingslashit($path) . '/';
        }
        $this->host    = aryget('host', $params, 'localhost');
        $this->port    = aryget('port', $params, '25');
        $this->user    = aryget('user', $params, 'ftp');
        $this->pwd     = aryget('password', $params, '');
        $this->timeout = intval(aryget('timeout', $params, 5));
        $this->passive = boolval(aryget('passive', $params));
    }

    public function getName():string {
        return 'FTP文件上传器';
    }

    public function save(string $filepath,?string $path = null) {
        if (!$this->ftp) {
            $this->initFtpConnection();
        }
        if (!$this->ftp) {
            return false;
        }
        $path = $this->getDestDir($path);

        $destdir = $path;
        if (!$this->checkDir($destdir)) {
            $this->last_error = '无法创建目录' . $destdir;

            return false;
        }
        $tmp_file = $filepath;

        $pathinfo = pathinfo($tmp_file);

        if ($this->filename) {
            $name = $this->filename . '.' . $pathinfo ['extension'];
        } else {
            $name = $pathinfo ['filename'] . '.' . $pathinfo ['extension'];
            $name = $this->unique_filename($destdir, $name);
        }
        $destfile = $destdir . $name;

        $result = @ftp_put($this->ftp, $this->homedir . $destfile, $tmp_file, FTP_BINARY);

        if ($result == false) {
            $this->last_error = '无法将文件[' . $tmp_file . ']上传到FTP服务器[' . $destfile . ']';

            return false;
        }
        $fileName = str_replace(DS, '/', $destfile);

        return ['url' => $fileName, 'name' => $pathinfo ['basename'], 'path' => $fileName];
    }

    public function delete($file) {
        if (!$this->ftp) {
            $this->initFtpConnection();
        }

        if (!$this->ftp) {
            return false;
        }

        return @ftp_delete($this->ftp, $this->homedir . untrailingslashit($file));
    }

    public function close() {
        if ($this->ftp) {
            @ftp_close($this->ftp);
        }
        $this->ftp = null;
    }

    private function checkDir($path) {
        $paths = explode('/', trim($path, '/'));
        foreach ($paths as $path) {
            if (!@ftp_chdir($this->ftp, $path)) {
                if (!@ftp_mkdir($this->ftp, $path)) {
                    @ftp_chdir($this->ftp, '~');

                    return false;
                }

                if (!@ftp_chdir($this->ftp, $path)) {
                    @ftp_chdir($this->ftp, '~');

                    return false;
                }
            }
        }

        @ftp_chdir($this->ftp, '~');

        return true;
    }

    public function getDestDir(?string $path = null) {
        if (!$path) {
            $dir = App::icfg('dir@media', 1);
            switch ($dir) {
                case 0:
                    $path = date('/Y/');
                    break;
                case 1:
                    $path = date('/Y/n/');
                    break;
                default:
                    $path = date('/Y/n/d/');
            }
            $rand_cnt = App::icfg('group_num@media', 0);
            if ($rand_cnt > 1) {
                $cnt  = rand(0, $rand_cnt - 1);
                $path .= $cnt . '/';
            }
        }

        return $this->path . untrailingslashit(ltrim($path, '/')) . '/';
    }

    private function unique_filename($dir, $name) {
        $dir = untrailingslashit($dir);
        @ftp_chdir($this->ftp, $this->homedir . $dir);
        $list = @ftp_nlist($this->ftp, '.');
        if ($list) {
            if (!in_array($name, $list) && !in_array('./' . $name, $list)) {
                @ftp_chdir($this->ftp, '~');

                return $name;
            }
            $filename = $name;
            $info     = pathinfo($filename);
            $ext      = !empty ($info ['extension']) ? '.' . $info ['extension'] : '';
            $name     = basename($filename, $ext);
            $i        = 1;
            while (true) {
                $name1 = $name . '_' . $i . $ext;
                if (!in_array($name1, $list) && !in_array('./' . $name1, $list)) {
                    $filename = $name1;
                    break;
                }
                $i++;
            }
        } else {
            $filename = $name;
        }
        @ftp_chdir($this->ftp, '~');

        return $filename;
    }

    public function initFtpConnection() {
        if (!function_exists('ftp_connect')) {
            $this->last_error = 'the ftp extension is not installed!';

            return null;
        }
        $this->ftp = @ftp_connect($this->host, $this->port, $this->timeout);
        if ($this->ftp && $this->user) {
            if (!@ftp_login($this->ftp, $this->user, $this->pwd)) {
                @ftp_close($this->ftp);
                $this->last_error = 'login fail!';
                $this->ftp        = null;
            }
        } else if (!$this->ftp) {
            $this->last_error = 'cannot connect to the ftp server';
        }
        if ($this->ftp) {
            if (!@ftp_pasv($this->ftp, $this->passive)) {
                @ftp_close($this->ftp);
                $this->ftp = null;

                return null;
            }
            if (!@ftp_chdir($this->ftp, '~')) {
                @ftp_close($this->ftp);
                $this->ftp = null;

                return null;
            }
            $this->homedir = trailingslashit(@ftp_pwd($this->ftp));
        }

        return $this->ftp;
    }

    public function configHint() {
        return 'host=主机地址&port=端口&user=用户名&password=密码&timeout=超时时间单位秒&passive=是否是被动模式(1|0)&path=路径';
    }

    public function configValidate($config) {
        $opts = str_replace(["\n", "\r"], ['&', ''], trim($config));
        @parse_str($opts, $params);
        $host    = aryget('host', $params, 'localhost');
        $port    = aryget('port', $params, '25');
        $user    = aryget('user', $params, 'ftp');
        $pwd     = aryget('password', $params, '');
        $timeout = intval(aryget('timeout', $params, 5));
        $passive = boolval(aryget('passive', $params));

        $ftp = @ftp_connect($host, $port, $timeout);
        if ($ftp && $user) {
            if (!@ftp_login($ftp, $user, $pwd)) {
                @ftp_close($ftp);

                return 'login fail - ' . $user;
            }
        } else if (!$ftp) {
            return 'cannot connect to the ftp server - ' . $host . ':' . $port;
        }
        if ($ftp) {
            if (!@ftp_pasv($ftp, $passive)) {
                @ftp_close($ftp);

                return 'cannot change passive mode to ' . $passive;
            }
            if (!@ftp_chdir($ftp, '~')) {
                @ftp_close($ftp);

                return 'cannot change to home dir';
            }
            @ftp_close($ftp);
        }

        return true;
    }
}