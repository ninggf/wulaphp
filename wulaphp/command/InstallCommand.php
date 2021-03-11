<?php

namespace wulaphp\command;

use wulaphp\app\App;
use wulaphp\artisan\ArtisanCommand;
use wulaphp\db\dialect\DatabaseDialect;

class InstallCommand extends ArtisanCommand {
    private $welcomeShow = false;

    public function cmd() {
        return 'install';
    }

    public function desc() {
        return 'install wulacms';
    }

    protected function execute($options) {
        $params  = $this->opt();
        $wulacms = $this->color->str('WulaCMS', 'red');
        if (!$this->welcomeShow) {
            $this->welcomeShow = true;
            $this->log('Welcome to the ' . $wulacms . ' Installer!');
            $this->log();
            $this->log(wordwrap($wulacms . ' is an ' . $this->color->str('open source, free', 'green') . ' CMS platform based on wulaphp.', 100));
        }
        $dashboard = 'backend';
        $domain    = '';
        if ($params) {
            $opts = @parse_query($params);
            if ($opts) {
                $env       = aryget('env', $opts, 'dev');
                $dbhost    = aryget('dbhost', $opts, 'localhost');
                $dbport    = aryget('dbport', $opts, '3306');
                $dbname    = aryget('dbname', $opts, 'wulacms');
                $dbuser    = aryget('dbuser', $opts, 'root');
                $dbpwd     = aryget('dbpwd', $opts, '');
                $dbcharset = aryget('charset', $opts, 'UTF8');
                $username  = aryget('username', $opts, 'admin');
                $password  = rand_str(12);

                $this->log();
                $this->log('install configuration:');

                $this->log('environment: ' . $env);
                $this->log('database info:');
                $this->log("\tserver  : " . $this->color->str($dbhost . ':' . $dbport, 'blue'));
                $this->log("\tdatabase: " . $this->color->str(str_pad($dbname, 20, ' ', STR_PAD_RIGHT), 'blue') . ' charset : ' . $this->color->str($dbcharset, 'blue'));
                $this->log("\tusername: " . $this->color->str(str_pad($dbuser, 20, ' ', STR_PAD_RIGHT), 'blue') . ' password: ' . $this->color->str($dbpwd, 'blue'));

                $this->log();
                $this->log('admin and dashboard:');
                $this->log("\tadmin    : " . $this->color->str($username, 'blue'));
                $this->log("\tdomain:" . $this->color->str($domain, 'blue'));
                $this->log();
                $this->log('is that correct? [Y/n] Y');

            } else {
                echo 'cannot parse parameters';

                return 1;
            }
        } else {
            $this->log('Now please flow the below steps to install it for you.');
            $this->log();
            $this->log('setp 1: environment');
            $this->log('-----------------------------------------------');
            $env = $this->get('environment [dev]', 'dev');

            $this->log();
            $this->log('setp 2: database');
            $this->log('-----------------------------------------------');
            $dbhost = $this->get('host [localhost]', 'localhost');

            do {
                $dbport = $this->get('port [3306]', '3306');
                if (!preg_match('#^[1-9]\d{1,3}$#', $dbport)) {
                    $this->log("\t" . $this->color->str('invalid prot number', null, 'red'));
                } else {
                    break;
                }
            } while (true);

            $dbname    = $this->get('dbname [wula]', 'wula');
            $dbcharset = strtoupper($this->get('charset [utf8mb4]', 'utf8mb4'));
            $dbuser    = $this->get('username [root]', 'root');
            $dbpwd     = $this->get('password');

            $this->log();
            $this->log('setp 3: confirm');
            $this->log('-----------------------------------------------');
            $this->log('environment: ' . $env);
            $this->log('database info:');
            $this->log("\tserver  : " . $this->color->str($dbhost . ':' . $dbport, 'blue'));
            $this->log("\tdatabase: " . $this->color->str(str_pad($dbname, 20, ' ', STR_PAD_RIGHT), 'blue') . ' charset : ' . $this->color->str($dbcharset, 'blue'));
            $this->log("\tusername: " . $this->color->str(str_pad($dbuser, 20, ' ', STR_PAD_RIGHT), 'blue') . ' password: ' . $this->color->str($dbpwd, 'blue'));

            $this->log();
            $confirm = strtoupper($this->get('is that correct? [Y/n]', 'Y'));
            if ($confirm !== 'Y') {
                return $this->execute($options);
            }
        }
        // install database
        $this->log();
        $this->log('step 4: create configuration files');
        $cfg = CONFIG_PATH . 'install_config.php';
        if (is_file($cfg)) {
            $dbconfig         = file_get_contents($cfg);
            $r['{dashboard}'] = $dashboard;
            $r['{domain}']    = $domain;
            $r["'{name}'"]    = 'null';
            $this->log('  create config.php ...', false);
            $dbconfig = str_replace(array_keys($r), array_values($r), $dbconfig);
            if (!@file_put_contents(CONFIG_PATH . 'config.php', $dbconfig)) {
                $this->error('cannot save configuration file ' . CONFIG_PATH . 'config.php');

                return 1;
            }
            $this->log('  [' . $this->color->str('done', 'green') . ']');
        }
        $dbconfig           = <<<'CFG'
<?php
/*
 * database configuration generated by installer. 
 */
$config = new \wulaphp\conf\DatabaseConfiguration('default');
$config->driver(env('db.driver', 'MySQL'));
$config->host(env('db.host', '{db.host}'));
$config->port(env('db.port', '{db.port}'));
$config->dbname(env('db.name', '{db.name}'));
$config->encoding(env('db.charset', '{db.charset}'));
$config->user(env('db.user', '{db.user}'));
$config->password(env('db.password', '{db.password}'));
$options = env('db.options', '');
if ($options) {
	$options = explode(',', $options);
	$dbops   = [];
	foreach ($options as $option) {
		$ops = explode('=', $option);
		if (count($ops) == 2) {
			if ($ops[1][0] == 'P') {
				$dbops[ @constant($ops[0]) ] = @constant($ops[1]);
			} else {
				$dbops[ @constant($ops[0]) ] = intval($ops[1]);
			}
		}
	}
	$config->options($dbops);
	$config['prefix']     = env('db.prefix', '');
    $config['persistent'] = env('db.persistent', '0');
}

return $config;
CFG;
        $r['{db.host}']     = $dbhost;
        $r['{db.port}']     = $dbport;
        $r['{db.name}']     = $dbname;
        $r['{db.charset}']  = $dbcharset;
        $r['{db.user}']     = $dbuser;
        $r['{db.password}'] = $dbpwd;
        $dbconfig           = str_replace(array_keys($r), array_values($r), $dbconfig);
        $this->log('  create dbconfig.php ...', false);
        if (!@file_put_contents(CONFIG_PATH . 'dbconfig.php', $dbconfig)) {
            $this->error('cannot save database configuration file ' . CONFIG_PATH . 'dbconfig.php');

            return 1;
        } else {
            $this->log('  [' . $this->color->str('done', 'green') . ']');
        }
        if ($env != 'pro') {
            $dcf[] = '[app]';
            $dcf[] = 'app.debug.level = warn';
            $dcf[] = 'app.mode = ' . $env;
            $dcf[] = '';
            $dcf[] = '[db]';
            $dcf[] = 'db.host = ' . $dbhost;
            $dcf[] = 'db.port = ' . $dbport;
            $dcf[] = 'db.name = ' . $dbname;
            $dcf[] = 'db.user = ' . $dbuser;
            $dcf[] = 'db.password = ' . $dbpwd;
            $dcf[] = 'db.charset = ' . $dbcharset;
            if (!@file_put_contents(CONFIG_PATH . '.env', implode("\n", $dcf))) {
                $this->error('cannot save .env file ');

                return 1;
            }
        }
        $dbconfig   = @include CONFIG_PATH . 'dbconfig.php';
        $siteConfig = @include CONFIG_PATH . 'install_config.php';
        try {
            // install modules
            $this->log();
            $this->log('step 5: install modules');
            $dbc = $dbconfig->toArray();
            unset($dbc['dbname']);
            $dialect = DatabaseDialect::getDialect($dbc);

            $dbs = $dialect->listDatabases();
            $rst = in_array($dbname, $dbs);
            if (!$rst) {
                $rst = $dialect->createDatabase($dbname, $dbcharset);
            }
            if (!$rst) {
                throw_exception('Cannot create the database ' . $dbname);
            }
            $db = App::db($dbconfig);
            if ($db == null) {
                throw_exception('Cannot connect to the database');
            }

            $modules = [];
            if (isset($siteConfig['modules'])) {
                $modules = array_merge($modules, (array)$siteConfig['modules']);
            }

            foreach ($modules as $m) {
                $this->log("  install " . $m . ' ... ', false);
                $md = App::getModuleById($m);
                if ($md instanceof CmfModule) {
                    if ($md->install($db, true)) {
                        $this->log('  [' . $this->color->str('done', 'green') . ']');
                    } else {
                        $this->log(' [' . $this->color->str('error', 'red') . ']');
                    }
                } else {
                    $this->log(' [' . $this->color->str('done', 'green') . ']');
                }
            }
        } catch (\Exception $e) {
            $this->error($e->getMessage());
            @unlink(CONFIG_PATH . '.env');
            @unlink(CONFIG_PATH . 'config.php');
            @unlink(CONFIG_PATH . 'dbconfig.php');

            return 1;
        }
        file_put_contents(CONFIG_PATH . 'install.lock', time());
        $this->log();
        $this->log('done: Congratulation');

        return 0;
    }

    private function get($promot = '', $default = '') {
        if ($promot) {
            echo $promot, ' : ';
            flush();
        }

        $line = trim(fgets(STDIN));
        if (!$line) {
            return $default;
        }

        return $line;
    }
}