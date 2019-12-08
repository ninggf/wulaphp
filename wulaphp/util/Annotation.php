<?php

namespace wulaphp\util;
/**
 * 用于解决类，方法的phpdoc以生成注解数据的类。通过此类应用程序可以在运行时动态的获取一些配置数据。
 *
 * @package wulaphp\util
 */
class Annotation {
    const IGNORE = [
        'global'         => 1,
        'link'           => 1,
        'inheritdoc'     => 1,
        'package'        => 1,
        'method'         => 1,
        'author'         => 1,
        'property'       => 1,
        'property-read'  => 1,
        'property-write' => 1,
        'version'        => 1,
        'since'          => 1
    ];
    protected $docComment  = '';
    protected $annotations = [];
    protected $remove      = true;

    /**
     * Annotation constructor.
     *
     * @param \Reflector|array $obj    可以是{@link \ReflectionObject},
     *                                 {@link \ReflectionMethod},
     *                                 {@link \ReflectionProperty},
     *                                 {@link \ReflectionFunction}的实例。
     * @param bool             $remove 是否删除换行,默认true
     */
    public function __construct($obj, bool $remove = true) {
        $this->remove = $remove;
        if (is_array($obj)) {
            $this->docComment  = '';
            $this->annotations = $obj;
        } else if (method_exists($obj, 'getDocComment')) {
            $this->docComment = $obj->getDocComment();
            if ($this->docComment) {
                $ignore           = self::IGNORE;
                $this->docComment = explode("\n", $this->docComment);
                $len              = count($this->docComment) - 1;
                $i                = 1;
                while ($i < $len) {
                    $doc = $this->docComment[ $i ];
                    $doc = substr(trim($doc), 1);
                    if ($doc && preg_match('#^@([a-z][a-z\d_]*)(\s+(.*))?#i', trim($doc), $ms)) {
                        $ann = $ms[1];
                        if (isset($ignore[ $ann ])) {
                            $i ++;
                            continue;
                        }

                        $value = isset($ms[3]) ? $ms[3] : '';
                        $value = $this->text($i, $value, $len, $this->remove ? '' : "\n");
                        if (isset($this->annotations[ $ann ])) {
                            if (is_array($this->annotations[ $ann ])) {
                                $this->annotations[ $ann ][] = $value;
                            } else {
                                $tmp                       = $this->annotations[ $ann ];
                                $this->annotations[ $ann ] = [$tmp, $value];
                            }
                        } else {
                            $this->annotations[ $ann ] = $value;
                        }
                    }
                    $i ++;
                }
                $i                = 0;
                $this->docComment = $this->text($i, '', $len, $sep = "\n", false);
            }
        }
    }

    /**
     * 是否有指定名称的注解.
     *
     * @param string $annotation
     *
     * @return bool
     */
    public function has(string $annotation): bool {
        return isset($this->annotations[ $annotation ]);
    }

    /**
     * 获取phpdoc文档.
     *
     * @return string
     */
    public function getDoc(): ?string {
        return $this->docComment;
    }

    /**
     * 获取字符串型注解.
     *
     * @param string $annotation
     * @param string $default
     *
     * @return string
     */
    public function getString(string $annotation, string $default = ''): string {
        if (isset($this->annotations[ $annotation ])) {
            if (is_array($this->annotations[ $annotation ])) {
                return $this->annotations[ $annotation ][0];
            } else {
                return $this->annotations[ $annotation ];
            }
        } else {
            return $default;
        }
    }

    /**
     * 获取整型注解.
     *
     * @param string $ann
     * @param int    $default
     *
     * @return int
     */
    public function getInt(string $ann, int $default = 0): int {
        $val = $this->getString($ann, $default);

        return intval(trim($val));
    }

    /**
     * 获取注解数组(多个同名注解或以逗号分隔)。
     *
     * @param string $annotation
     * @param array  $default
     *
     * @return array
     */
    public function getArray(string $annotation, array $default = []): array {
        $str = $this->getStrings($annotation);
        if (is_array($str)) {
            return $str;
        }
        if ($str) {
            $str = trim($str);
            $str = pure_comman_string($str);

            return explode(',', $str);
        }

        return $default;
    }

    /**
     * 获取同名注解值数组.
     *
     * @param string $annotation
     * @param array  $default
     *
     * @return array
     */
    public function getMultiValues(string $annotation, array $default = []): array {
        $str = $this->getStrings($annotation);
        if (is_array($str)) {
            return $str;
        }
        if ($str) {
            return [$str];
        }

        return $default;
    }

    /**
     * 获取bool型注解值.
     *
     * @param string $name
     * @param bool   $default
     *
     * @return bool
     */
    public function getBool(string $name, bool $default = false): bool {
        if (!$this->has($name)) {
            return $default;
        }
        $va = strtolower($this->getString($name));
        if (in_array($va, ['yes', '1', 'on', 'true'])) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * JSON格式转array注解值.可以是：
     *
     * 1. 同名注解，key与value用空格分隔,仅支持一级
     * 2. json格式字符串.
     * 3. http GET 参数形式.
     *
     * @param string $annotation
     * @param array  $default
     *
     * @return array
     */
    public function getJsonArray(string $annotation, array $default = []): array {
        $str = $this->getStrings($annotation);
        if (is_array($str)) {
            $rst = [];
            foreach ($str as $s) {
                $ss = preg_split('/\s+/', $s, 2);
                if (count($ss) == 2) {
                    [$k, $v] = $ss;
                } else {
                    $k = $ss[0];
                    $v = 1;
                }
                $rst[ $k ] = $v;
            }

            return $rst;
        }
        if ($str) {
            $str = trim($str);
            if (preg_match('#^[\[\{](.*)[\}\]]$#', $str)) {
                $rst = json_decode($str, true);
                if ($rst) {
                    return $rst;
                } else {
                    trigger_error($str . ' ' . json_last_error_msg(), E_USER_WARNING);
                }
            } else {
                @parse_str($str, $rst);
                if ($rst) {
                    return $rst;
                }
                trigger_error($str . ' Syntax error', E_USER_WARNING);
            }
        }

        return $default;
    }

    /**
     * 取全部注解.
     *
     * @return array
     */
    public function getAll(): array {
        return $this->annotations;
    }

    private function getStrings(string $ann, string $default = '') {
        return isset($this->annotations[ $ann ]) ? $this->annotations[ $ann ] : $default;
    }

    private function text(int &$i, string $text, int $len, string $sep = '') {
        $j = $i + 1;

        while ($j < $len) {
            $val = substr(trim($this->docComment[ $j ]), 1);
            $ov  = trim($val);
            if ($ov && $ov[0] == '@') {
                $j --;//归位
                break;
            } else if ($sep) {
                $val = substr($val, 1);
            } else {
                $val = trim($val);
            }
            $text .= $sep . $val;
            $j ++;
        }
        $i = $j;

        return trim($text);
    }
}
