<?php
namespace wulaphp\db\sql;

class InsertSQL extends QueryBuilder implements \ArrayAccess, \IteratorAggregate {

	private $intoTable;

	private $datas;

	private $batch;

	private $ids = array();

	private $keyField = null;

	public function __construct($datas, $batch = false) {
		$this->datas = $datas;
		$this->batch = $batch;
	}

	/**
	 * specify the auto increment key then
	 *
	 * @param string $key
	 *
	 * @return InsertSQL
	 */
	public function autoKey($key) {
		$this->keyField = $key;

		return $this;
	}

	/**
	 * the datas will be inserted into whitch table.
	 *
	 * @param string $table
	 *
	 * @return QueryBuilder
	 */
	public function into($table) {
		$this->intoTable = $table;

		return $this;
	}

	/**
	 * just use count() function to perform this SQL and get the affected rows(inserted)
	 *
	 * @see Countable::count()
	 * @return int
	 */
	public function count() {
		if (empty ($this->intoTable)) {
			$this->error = 'no table specified!';

			return false;
		}
		if (empty ($this->datas)) {
			$this->error = 'no data to insert!';

			return false;
		}
		$this->checkDialect();
		$values    = new BindValues ();
		$ids       = array_keys($this->datas);
		$data      = $this->batch ? $this->datas [ $ids [0] ] : $this->datas;
		$into      = $this->prepareFrom(array(array($this->intoTable, null)));
		$sql       = $this->dialect->getInsertSQL($into [0] [0], $data, $values);
		$this->sql = $sql;
		if ($sql) {
			try {
				if ($this->batch && count($this->datas) > 1) {
					unset($ids[0]);
					$sqlValues = [];
					foreach ($ids as $idx) {
						$d       = $this->datas[ $idx ];
						$vstring = [];
						foreach ($d as $ff => $vv) {
							if ($vv instanceof ImmutableValue) { // a immutable value
								$vv->setDialect($this->dialect);
								$vstring [] = $this->sanitize($vv->__toString());
							} else if ($vv instanceof Query) { // a sub-select SQL as a value
								$vv->setBindValues($values);
								$vv->setDialect($this->dialect);
								$vstring [] = '(' . $vv->__toString() . ')';
							} else {
								$vstring [] = $values->addValue($ff, $vv);
							}
						}
						$sqlValues[] = '(' . implode(',', $vstring) . ')';
					}
					$this->sql = $sql = $sql . ',' . implode(',', $sqlValues);
				}

				$statement = $this->dialect->prepare($sql);

				foreach ($values as $value) {
					list ($name, $val, $type) = $value;
					if (!$statement->bindValue($name, $val, $type)) {
						$this->errorSQL    = $sql;
						$this->errorValues = $values->__toString();
						$this->error       = 'can not bind the value ' . $val . '[' . $type . '] to the argument:' . $name;
						log_error($this->error . ' [' . $this->errorSQL . ']', 'sql');

						return false;
					}
				}

				$rst = $statement->execute();
				if ($rst) {
					$this->ids [] = $this->dialect->lastInsertId($this->keyField);

					return 1;
				} else {
					$this->dumpSQL($statement);
				}

				if ($statement) {
					$statement->closeCursor();
					$statement = null;
				}
			} catch (\PDOException $e) {
				$this->exception   = $e;
				$this->error       = $e->getMessage();
				$this->errorSQL    = $sql;
				$this->errorValues = $values->__toString();
			}
		} else {
			$this->error       = 'Can not generate the insert SQL';
			$this->errorSQL    = '';
			$this->errorValues = $values->__toString();
		}
		if ($this->error) {
			log_error($this->error . ' [' . $this->errorSQL . ']', 'sql');
		}

		return false;
	}

	public function offsetExists($offset) {
		return isset ($this->ids [ $offset ]);
	}

	public function offsetGet($offset) {
		return $this->ids [ $offset ];
	}

	public function lastInsertIds() {
		return $this->ids;
	}

	public function offsetSet($offset, $value) {
	}

	public function offsetUnset($offset) {
	}

	/**
	 * get the last inserted id
	 *
	 * @param string $name
	 *
	 * @return int
	 */
	public function lastId($name = null) {
		$this->checkDialect();

		return $this->dialect->lastInsertId($name);
	}

	public function getIterator() {
		return new \ArrayIterator ($this->ids);
	}

	public function getSqlString() {
		return $this->sql;
	}
}
