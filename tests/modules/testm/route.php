<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

return [
    'mul.html' => [
        'template'     => 'mul.tpl',
        'expire'       => 100,
        'func'         => function ($data) {
            $data['num1'] = 20 * $data['base'];

            return $data;
        },
        'Content-Type' => 'text/html',
        'data'         => ['base' => 10]
    ]
];