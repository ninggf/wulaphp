<?php

namespace wulaphp\util;

abstract class TraitObject {
	protected $myTraits = [];

	public function __construct() {
		$parents = class_parents($this);
		array_pop($parents);
		if ($parents) {
			$traits = class_uses($this);
			foreach ($parents as $p) {
				$tt = class_uses($p);
				if ($tt) {
					$traits = array_merge($traits, $tt);
				}
			}
			if ($traits) {
				foreach ($traits as $tt) {
					$tts                   = explode('\\', $tt);
					$fname                 = $tts[ count($tts) - 1 ];
					$this->myTraits[ $tt ] = $fname;
				}
			}
		}
		unset($parents, $traits);
	}
}