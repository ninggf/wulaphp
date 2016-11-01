<?php

namespace wulaphp\auth;

interface IRbac {
	/**
	 *
	 * @param \wulaphp\auth\Passport $passport
	 * @param string                 $res
	 * @param null                   $extra
	 *
	 * @return bool
	 */
	public function icando(Passport $passport, $res, $extra = null);

	/**
	 * @param \wulaphp\auth\Passport $passport
	 * @param  string                $role
	 *
	 * @return bool
	 */
	public function iam(Passport $passport, $role);
}