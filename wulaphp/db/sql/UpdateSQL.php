<?php
namespace wulaphp\db\sql;

/**
 * update SQL
 *
 * @author guangfeng.ning
 *
 */
class UpdateSQL extends QueryBuilder {

	private $data  = array();
	private $batch = false;

	public function __construct($table) {
		if (is_array($table)) {
			foreach ($table as $t) {
				$this->from($t);
			}
		} else {
			$this->from($table);
		}
	}

	/**
	 * the data to be updated
	 *
	 * @param array $data
	 * @param bool  $batch
	 *
	 * @return UpdateSQL
	 */
	public function set($data, $batch = false) {
		$this->data  = $data;
		$this->batch = $batch;

		return $this;
	}

	public function count() {
		if (empty ($this->from)) {
			$this->error = 'no table specified!';

			return false;
		}
		$this->checkDialect();
		$values = new BindValues ();
		$froms  = $this->prepareFrom($this->sanitize($this->from));
		$order  = $this->sanitize($this->order);
		$ids    = array_keys($this->data);
		$data   = $this->batch ? $this->data[ $ids [0] ][0] : $this->data;
		if ($this->batch) {
			$this->where($this->data[ $ids [0] ][1]);
		}
		$sql       = $this->dialect->getUpdateSQL($froms, $data, $this->where, $values, $order, $this->limit);
		$this->sql = $sql;
		if ($sql) {
			try {
				$statement = $this->dialect->prepare($sql);
				$cnt       = false;
				if ($this->batch) {
					$cnt = 0;
					foreach ($this->data as $data) {
						list($da, $where) = $data;
						foreach ($values as $value) {
							list ($name, $val, $type, $key, $rkey) = $value;
							$rval = isset($where[ $rkey ]) ? $where[ $rkey ] : (isset($da[ $rkey ]) ? $da[ $rkey ] : $val);
							if (!$statement->bindValue($name, $rval, $type)) {
								$this->errorSQL    = $sql;
								$this->errorValues = $values->__toString();
								$this->error       = 'can not bind the value ' . $rval . '[' . $type . '] to the argument:' . $name;
								log_warn($this->error . ' [' . $this->errorSQL . ']', 'sql');

								return false;
							}
						}
						$rst = $statement->execute();
						QueryBuilder::addSqlCount();

						if ($rst) {
							$cnt += $statement->rowCount();
						} else {
							$this->dumpSQL($statement);

							break;
						}
					}
				} else {
					foreach ($values as $value) {
						list ($name, $val, $type) = $value;
						if (!$statement->bindValue($name, $val, $type)) {
							$this->errorSQL    = $sql;
							$this->errorValues = $values->__toString();
							$this->error       = 'can not bind the value ' . $val . '[' . $type . '] to the argument:' . $name;
							log_warn($this->error . ' [' . $this->errorSQL . ']', 'sql');

							return false;
						}
					}
					$rst = $statement->execute();
					QueryBuilder::addSqlCount();

					if ($rst) {
						$cnt = $statement->rowCount();
					} else {
						$this->dumpSQL($statement);
					}
				}

				if ($statement) {
					$statement->closeCursor();
					$statement = null;
				}

				return $cnt;
			} catch (\PDOException $e) {
				$this->exception   = $e;
				$this->error       = $e->getMessage();
				$this->errorSQL    = $sql;
				$this->errorValues = $values->__toString();
			}
		} else {
			$this->error       = 'Can not generate the delete SQL';
			$this->errorSQL    = '';
			$this->errorValues = $values->__toString();
		}
		if ($this->error) {
			log_warn($this->error . ' [' . $this->errorSQL . ']', 'sql');
		}

		return false;
	}

	public function getSqlString() {
		return $this->sql;
	}

}
