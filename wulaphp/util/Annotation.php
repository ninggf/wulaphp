<?php

namespace wulaphp\util;

class Annotation {
	const IGNORE = ['param' => 1, 'return' => 1, 'global' => 1, 'see' => 1, 'use' => 1, 'internal' => 1, 'link' => 1, 'deprecated' => 1, 'inheritdoc' => 1, 'package' => 1, 'method'];
	protected $docComment;
	protected $annotations = [];

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
				foreach ($this->docComment as $i => $doc) {
					$doc = substr(trim($doc), 1);
					if ($doc && preg_match('#^@([a-z][a-z\d_]*)(\s+(.*))?#', trim($doc), $ms)) {
						$ann = $ms[1];
						if (isset($ignore[ $ann ])) {
							continue;
						}
						$value                     = isset($ms[3]) ? $ms[3] : '';
						$this->annotations[ $ann ] = $value;
					}
				}
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
			if (preg_match('#^(\[\{)(.*)(\]\})$#', $str, $ms)) {
				$rst = @json_decode($str, true);
				if ($rst !== false) {
					return $rst;
				}
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
}
