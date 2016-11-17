<?php

namespace wulaphp\db;

use wulaphp\db\sql\DeleteSQL;
use wulaphp\db\sql\InsertSQL;
use wulaphp\db\sql\UpdateSQL;
use wulaphp\validator\ValidateException;

/**
 * 表基类,提供与表相关的简单操作。
 *
 * @package wulaphp\db
 * @author  Leo Ning <windywany@gmail.com>
 */
abstract class Table extends View {
	protected $autoIncrement = true;

	/**
	 * Table constructor.
	 *
	 * @param \wulaphp\db\DatabaseConnection $db
	 */
	public function __construct(DatabaseConnection $db = null) {
		parent::__construct($db);
		$this->parseTraits();
	}

	/**
	 * 创建记录.
	 *
	 * @param array    $data 数据.
	 * @param \Closure $cb   数据处理函数.
	 *
	 * @return bool|int 成功返回true或主键值,失败返回false.
	 * @throws ValidateException
	 */
	public function insert($data, $cb = null) {
		if ($cb && $cb instanceof \Closure) {
			$data = $cb ($data, $this);
		}
		if ($data) {
			if (method_exists($this, 'validateNewData')) {
				$this->validateNewData($data);
			}
			$sql = new InsertSQL($data);
			$sql->into($this->table)->setDialect($this->dialect);
			if ($this->autoIncrement) {
				$rst = $sql->exec();
				if ($rst && $rst [0]) {
					$rst = $rst [0];
				}
			} else {
				$rst = $sql->exec(true);
			}
			if ($rst) {
				return $rst;
			} else {
				$this->checkSQL($sql);

				return false;
			}
		} else {
			$this->errors = '数据为空.';

			return false;
		}
	}

	/**
	 * 批量插入数据.
	 *
	 * @param array    $datas 要插入的数据数组.
	 * @param \Closure $cb
	 *
	 * @return bool|array 如果配置了自增键将返回自增键值的数组.
	 */
	public function inserts($datas, \Closure $cb = null) {
		if ($cb && $cb instanceof \Closure) {
			$datas = $cb ($datas, $this);
		}
		if ($datas) {
			if (method_exists($this, 'validateNewData')) {
				foreach ($datas as $data) {
					$this->validateNewData($data);
				}
			}
			$sql = new InsertSQL($datas, true);
			$sql->into($this->table)->setDialect($this->dialect);
			if ($this->autoIncrement) {
				$rst = $sql->exec();
			} else {
				$rst = $sql->exec(true);
			}
			if ($rst) {
				return $rst;
			} else {
				$this->checkSQL($sql);

				return false;
			}
		} else {
			$this->errors = '数据为空.';

			return false;
		}
	}

	/**
	 * 更新数据.
	 *
	 * @param array    $data 数据.
	 * @param array    $con  更新条件.
	 * @param \Closure $cb   数据处理器.
	 *
	 * @return bool 成功true，失败false.
	 * @throws ValidateException
	 */
	public function update($data, $con = null, $cb = null) {
		if ($con && !is_array($con)) {
			$con = [$this->primaryKeys[0] => $con];
		}
		if (!$con) {
			$con = $this->getWhere($data);
			if (count($con) != count($this->primaryKeys)) {
				$this->errors = '未提供更新条件';

				return false;
			}
		}
		if (empty ($con)) {
			$this->errors = '更新条件为空';

			return false;
		}
		if ($cb && $cb instanceof \Closure) {
			$data = $cb ($data, $con, $this);
		}
		if ($data) {
			if (method_exists($this, 'validateUpdateData')) {
				$this->validateUpdateData($data);
			}
			$sql = new UpdateSQL($this->table);
			$sql->set($data)->setDialect($this->dialect)->where($con);
			$rst = $sql->exec();
			$this->checkSQL($sql);

			return $rst;
		} else {
			return false;
		}
	}

	/**
	 * 删除记录.
	 *
	 * @param array|int $con 条件或主键.
	 *
	 * @return boolean 成功true，失败false.
	 */
	public function delete($con) {
		$rst = false;
		if (is_int($con)) {
			$con[ $this->primaryKeys[0] ] = $con;
		}
		if ($con) {
			$sql = new DeleteSQL();
			$sql->from($this->table)->setDialect($this->dialect);
			$sql->where($con);
			$rst = $sql->exec();
			$this->checkSQL($sql);
		}

		return $rst;
	}

	/**
	 * 回收内容，适用于软删除(将deleted置为1).
	 * 所以表中需要有deleted的字段,当其值为1时表示删除.
	 * 如果uid不为0,则表中还需要有update_time与update_uid字段,
	 * 分别表示更新时间与更新用户.
	 *
	 * @param array    $con 条件.
	 * @param int      $uid 如果大于0，则表中必须包括update_time(unix时间戳)和update_uid字段.
	 * @param \Closure $cb  回调.
	 *
	 * @return boolean 成功true，失败false.
	 */
	public function recycle($con, $uid = 0, $cb = null) {
		if (!$con) {
			return false;
		}
		$data ['deleted'] = 1;
		if ($uid) {
			$data ['update_time'] = time();
			$data ['update_uid']  = $uid;
		}
		$rst = $this->update($data, $con);
		if ($rst && $cb instanceof \Closure) {
			$cb ($con, $this);
		}

		return $rst;
	}

	/**
	 * 初始化Traits.
	 */
	private function parseTraits() {
		$parents = class_parents($this);
		unset($parents['wulaphp\db\Table']);
		$traits = class_uses($this);
		if ($parents) {
			foreach ($parents as $p) {
				$tt = class_uses($p);
				if ($tt) {
					$traits = array_merge($traits, $tt);
				}
			}
		}
		if ($traits) {
			foreach ($traits as $tt) {
				$tts   = explode('\\', $tt);
				$fname = $tts[ count($tts) - 1 ];
				$func  = 'onInit' . $fname;
				if (method_exists($this, $func)) {
					$this->$func();
				}
			}
		}
		unset($parents, $traits);
	}
}