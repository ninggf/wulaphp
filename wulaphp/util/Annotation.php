<?php

namespace wulaphp\util;

class Annotation {
	const IGNORE = [
		'param'      => 1,
		'return'     => 1,
		'global'     => 1,
		'see'        => 1,
		'use'        => 1,
		'internal'   => 1,
		'link'       => 1,
		'deprecated' => 1,
		'inheritdoc' => 1,
		'package'    => 1,
		'method'     => 1,
		'author'     => 1,
		'property'   => 1,
		'version'    => 1,
		'since'      => 1,
		'throws'     => 1
	];
	protected $docComment  = '';
	protected $annotations = [];
	private   $anns        = [];

	/**
	 * Annotation constructor.
	 *
	 * @param \Reflector $obj 可以是{@link \ReflectionObject},
	 *                        {@link \ReflectionMethod},
	 *                        {@link \ReflectionProperty},
	 *                        {@link \ReflectionFunction}的实例。
	 */
	public function __construct(\Reflector $obj) {
		if (method_exists($obj, 'getDocComment')) {
			$this->docComment = $obj->getDocComment();
			$ignore           = self::IGNORE;
			if ($this->docComment) {
				$this->docComment = explode("\n", $this->docComment);
				$len              = count($this->docComment) - 1;
				$i                = 1;
				while ($i < $len) {
					$doc = $this->docComment[ $i ];
					$doc = substr(trim($doc), 1);
					if ($doc && preg_match('#^@([a-z][a-z\d_]*)(\s+(.*))?#i', trim($doc), $ms)) {
						$ann = $ms[1];
						if (isset($ignore[ $ann ])) {
							$i++;
							continue;
						}

						$value = isset($ms[3]) ? $ms[3] : '';
						$value = $this->text($i, $value, $len);
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
					$i++;
				}
				$i                = 0;
				$this->docComment = $this->text($i, '', $len, $sep = "\n");
			}
		}
	}

	/**
	 * @param string $annotation
	 *
	 * @return bool
	 */
	public function has($annotation) {
		return isset($this->annotations[ $annotation ]);
	}

	/**
	 * @return string
	 */
	public function getDoc() {
		return $this->docComment;
	}

	/**
	 * @param string $annotation
	 * @param string $default
	 *
	 * @return mixed|string
	 */
	public function getString($annotation, $default = '') {
		return isset($this->annotations[ $annotation ]) ? $this->annotations[ $annotation ] : $default;
	}

	/**
	 * @param string $annotation
	 * @param array  $default
	 *
	 * @return array
	 */
	public function getArray($annotation, $default = []) {
		$str = $this->getString($annotation);
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
	 * @param string $annotation
	 * @param array  $default
	 *
	 * @return array|mixed
	 */
	public function getJsonArray($annotation, $default = []) {
		$str = $this->getString($annotation);
		if ($str) {
			$str = trim($str);
			if (preg_match('#^[\[\{](.*)[\}\]]$#', $str)) {
				$rst = json_decode($str, true);
				if ($rst) {
					return $rst;
				} else {
					log_warn($str . ' ' . json_last_error_msg());
				}
			} else {
				log_warn($str . ' Syntax error');
			}
		}

		return $default;
	}

	/**
	 * 取全部注解.
	 *
	 * @return array
	 */
	public function getAll() {
		return $this->annotations;
	}

	private function text(&$i, $text, $len, $sep = '') {
		$j = $i + 1;

		while ($j < $len) {
			$val = trim(substr(trim($this->docComment[ $j ]), 1));
			if ($val{0} == '@') {
				$j--;//归位
				break;
			} else if ($val) {
				$text .= $sep . $val;
			}
			$j++;
		}
		$i = $j;

		return trim($text);
	}
}
