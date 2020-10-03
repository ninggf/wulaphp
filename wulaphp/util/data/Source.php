<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace wulaphp\util\data;
/**
 * 批处理数据源. 通过`data`方法提供数据，
 * 通过`operator`进行数据处理，
 * 通过`sink`将处理后的数据导出，
 * `run`启动批处理。
 *
 * @package wulaphp\util\data
 */
abstract class Source {
    /**
     * @var \wulaphp\util\data\Operator[]
     */
    private $ops = [];
    /**
     * @var \wulaphp\util\data\Sinker[]
     */
    private $sinker = [];
    /**
     * @var \wulaphp\util\data\State
     */
    protected $state;

    /**
     * Source constructor.
     *
     * @param \wulaphp\util\data\State|null $state 状态管理器
     *
     * @throws \InvalidArgumentException when name is empty
     */
    public function __construct(?State $state = null) {
        $this->state = $state ?? new State();
    }

    /**
     * 添加数据处理器.
     *
     * @param \wulaphp\util\data\Operator $operator
     *
     * @return \wulaphp\util\data\Source
     */
    public final function operate(Operator $operator): Source {
        $operator->setState($this->state);
        $this->ops[] = $operator;

        return $this;
    }

    /**
     * 输出.
     *
     * @param \wulaphp\util\data\Sinker $sinker
     *
     * @return \wulaphp\util\data\Source
     */
    public final function sink(Sinker $sinker): Source {
        $sinker->setState($this->state);
        $this->sinker[] = $sinker;

        return $this;
    }

    /**
     * dump
     */
    public function dump() {
        $this->sink(new DumpSinker());

        $this->run();
    }

    /**
     * 在此数据源上执行批处理.
     */
    public function run() {
        set_time_limit(0);
        $datas   = $this->data();
        $sinkers = [];
        foreach ($this->sinker as $sk) {
            if ($sk->onStarted()) {
                $sinkers[] = $sk;
            }
        }
        if ($datas) {
            foreach ($datas as $data) {
                $dd     = $data;
                $sinked = true;
                foreach ($this->ops as $operator) {
                    if ($dd === null) {
                        break;
                    }
                    $dd = $operator->operate($data);
                }
                foreach ($sinkers as $sk) {
                    if ($dd === null) {
                        break;
                    }
                    $sinked = $sinked && $sk->sink($data);
                }
                if ($sinked) {
                    $this->onSinked($data);
                }
            }
            foreach ($sinkers as $sk) {
                $sk->onCompleted();
            }
        } else {
            foreach ($sinkers as $sk) {
                $sk->noData();
                $sk->onCompleted();
            }
        }
    }

    /**
     * @param mixed $data 数据下沉完成.
     */
    protected function onSinked($data) {
    }

    /**
     * 数据生成器. 需要通过`yield`生成需要处理的数据.
     *
     * @return \Generator|null
     */
    protected abstract function data(): ?\Generator;
}