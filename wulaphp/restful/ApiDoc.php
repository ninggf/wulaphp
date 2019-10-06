<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace wulaphp\restful;

use wulaphp\app\App;
use wulaphp\util\Annotation;

class ApiDoc {
    /**
     * 获取API文档.
     *
     * @param string     $api    API
     * @param int|string $v      版本
     * @param string     $method 方法
     *
     * @return array
     */
    public static function doc($api, $v, $method = 'get') {
        $apis = explode('.', $api);
        if (count($apis) != 3) {
            return ['document' => 'API不合法'];
        }
        $namesapce = $apis[0];
        $module    = App::getModuleById($namesapce);
        if (!$module) {
            return ['document' => '模块不存在'];
        }
        if (is_int($v)) {
            $v = 'v' . $v;
        }
        $cls  = ucfirst($apis[1]) . 'Api';
        $cls  = $namesapce . '\\api\\' . $v . '\\' . $cls;
        $data = ['document' => null, 'params' => null, 'paramos' => null, 'errors' => null, 'return' => null];
        if (class_exists($cls) && is_subclass_of($cls, API::class)) {
            try {
                /**@var API $clz */
                $clz = new $cls('');
                $ref = new \ReflectionObject($clz);

                if ($method == 'post') {
                    $m     = $ref->getMethod($apis[2] . 'Post');
                    $label = '<span class="label bg-success">POST</span> `' . $api . '`';
                } else {
                    $m     = $ref->getMethod($apis[2]);
                    $label = '<span class="label bg-info">GET</span> `' . $api . '`';
                }
                if (!$m) {
                    return ['document' => 'API不存在'];
                }
                $ann  = new Annotation($m);
                $sess = $ann->has('session');

                $markdown[] = ($sess ? '<span class="label bg-warning">SESSION</span> ' : '') . $label;

                $apiName    = $ann->getString('apiName', ucfirst($apis[2]));
                $markdown[] = "\n## " . $apiName;

                $markdown[] = $ann->getDoc();

                $data['document'] = implode("\n", $markdown);

                $markdown = ["\n### 请求参数\n"];
                $args     = [];
                foreach ($m->getParameters() as $p) {
                    if ($p->isOptional()) {
                        $args[ $p->getName() ] = $p->getDefaultValue();
                    }
                }
                $params = $ann->getMultiValues('param');
                $inputs = [];
                if ($params) {
                    $markdown[] = '|名称|类型|是否必须|默认值|示例值|描述|';
                    $markdown[] = '|:---|:---:|:---:|:---:|:---|:---|';
                    foreach ($params as $param) {
                        if (preg_match('/([^\s]+)\s+\$([^\s]+)(\s+(\((?P<req>required,?)?(\s*sample=(?P<sample>.+?))?\)\s*)?(?P<desc>.*))?/', $param, $ms)) {
                            $ms[1]            = ucfirst($ms[1]);
                            $req              = $ms['req'] != '' ? 'Y' : 'N';
                            $sample           = $ms['sample'];
                            $dv               = isset($args[ $ms[2] ]) ? $args[ $ms[2] ] : '';
                            $markdown[]       = "|{$ms[2]}|{$ms[1]}|{$req}|$dv|{$sample}|{$ms['desc']}|";
                            $inputs[ $ms[2] ] = [$ms[1], $req, $dv, $sample, $ms['desc']];
                        }
                    }
                } else {
                    $markdown[] = '无';
                }
                $data['params'] = implode("\n", $markdown);

                //输出数据
                $paramos = $ann->getMultiValues('paramo');
                if ($paramos) {
                    $ps   = ['### 输出数据'];
                    $ps[] = '|名称|类型|描述|';
                    $ps[] = '|:---|:---:|:---|';
                    foreach ($paramos as $pb) {
                        if (preg_match('/^([^\s]+)\s+([^\s]+)(\s+.*)$/', $pb, $ms)) {
                            $ms[1] = ucfirst($ms[1]);
                            $nm    = preg_replace('#^\.+#', '&nbsp;&nbsp;&nbsp;', $ms[2]);
                            $ps[]  = "|{$nm}|{$ms[1]}|{$ms[3]}|";
                        }
                    }
                    $data['paramos'] = implode("\n", $ps);
                }
                //返回数据
                $rtnData    = ['### 响应示例'];
                $rtnData [] = '```json';
                $rtn        = $ann->getString('return');
                if (preg_match('/^(.+?)\s+(.+)$/', $rtn, $ms)) {
                    $rtn  = $ms[2];
                    $rstr = '{"response":' . $rtn . '}';
                    $rtn  = @json_decode($rstr, true);
                    if ($rtn) {
                        $rtn = json_encode($rtn, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    } else {
                        $rtn = $rstr;
                    }
                } else {
                    $rtn = '{}';
                }
                $rtnData []     = $rtn;
                $rtnData []     = '```';
                $data['return'] = implode("\n", $rtnData);
                //错误代码
                $errors = $ann->getMultiValues('error');
                if ($errors) {
                    $rtnData    = ["\n### 异常示例\n"];
                    $rtnData [] = '```json';
                    $rtnData [] = json_encode(json_decode('{"response":{"error":{"code":405,"msg":"非法请求"}}}', true), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                    $rtnData [] = '```';
                    $rtnData[]  = "\n#### 异常代码";
                    $rtnData[]  = '|代码|描述|';
                    $rtnData[]  = '|:---|:---|';
                    foreach ($errors as $error) {
                        $ers       = explode('=>', trim($error));
                        $rtnData[] = "|{$ers[0]}|{$ers[1]}|";
                    }
                    $data['errors'] = implode("\n", $rtnData);
                }

            } catch (\Exception $e) {
                return ['document' => $e->getMessage()];
            }
        }

        return $data;
    }

    /**
     * 描述所有API.
     *
     * @return array
     */
    public static function scan() {
        $data    = [];
        $modules = App::modules('enabled');
        foreach ($modules as $mid => $module) {
            $path = $module->getPath('api');
            if (is_dir($path)) {
                $ms = [
                    'id'       => $mid,
                    'title'    => $module->getName(),
                    'isParent' => true
                ];
                self::scanVers($ms, $path, $mid);
                $data[] = $ms;

            }
        }

        return $data;
    }

    private static function scanVers(&$ms, $path, $id) {
        $data = [];
        $dir  = new \DirectoryIterator($path);
        foreach ($dir as $d) {
            if ($d->isDot()) continue;
            $ns = $d->getFilename();
            if (!preg_match('/^v\d+$/', $ns)) continue;
            $vid = $id . '/' . $ns;
            $nss = [
                'id'       => $vid,
                'title'    => $ns,
                'isParent' => true
            ];
            self::scanApis($nss, $path . DS . $ns, $vid);
            $data[] = $nss;
        }
        if ($data) {
            $ms['children'] = $data;
        }
    }

    private static function scanApis(&$ms, $path, $id) {
        $data = [];
        $dir  = new \DirectoryIterator($path);
        $ids  = explode('/', $id);
        $idx  = $ids[0];
        foreach ($dir as $d) {
            if ($d->isDot()) continue;
            $ns   = strstr($d->getFilename(), '.', true);
            $cls  = $idx . '\\api\\' . $ids[1] . '\\' . $ns;
            $apic = [];
            if (class_exists($cls) && is_subclass_of($cls, API::class)) {
                $clz              = new $cls('');
                $ref              = new \ReflectionObject($clz);
                $cna              = new Annotation($ref);
                $apic['id']       = $id . '/' . $ns;
                $apic['title']    = $cna->getString('name', preg_replace('/Api$/', '', $ns));
                $apic['isParent'] = true;
                $methods          = $ref->getMethods(\ReflectionMethod::IS_PUBLIC);
                $rname            = lcfirst(preg_replace('/Api$/', '', $ref->getShortName()));
                $children         = [];
                foreach ($methods as $method) {
                    if ($method->isStatic()) {
                        continue;
                    }
                    $name = $method->getName();
                    if (preg_match('/^(__.+|setup|tearDown)$/', $name)) continue;
                    $mna        = new Annotation($method);
                    $funcName   = preg_replace('/(Post|Put|Delete)$/i', '', $name);
                    $api        = $ids[0] . '.' . $rname . '.' . $funcName;
                    $children[] = [
                        'id'     => md5($api . $ids[1] . $name),
                        'api'    => $api,
                        'title'  => $mna->getString('apiName', $funcName),
                        'ver'    => $ids[1],
                        'method' => preg_match('/^.+(Post|Put|Delete)$/i', $name, $ms) ? strtolower($ms[1]) : 'get'
                    ];
                }
                $apic['children'] = $children;
            }
            $data[] = $apic;
        }
        if ($data) {
            $ms['children'] = $data;
        }
    }
}