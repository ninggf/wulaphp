<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace app\gearman;

use wulaphp\artisan\GmWorker;

class ReverseWorker extends GmWorker {

    protected function doJob($workload): bool {
        if ($workload == 'def') {
            return false;
        } else {
            $this->send(strrev($workload));
        }

        return true;
    }
}