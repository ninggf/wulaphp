<?php
namespace wulaphp\db\sql;

/**
 * update SQL
 *
 * @author guangfeng.ning
 *
 */
class UpdateSQL extends QueryBuilder {

	private $data = array();

	public function __construct($table) {
		$this->from($table);
	}

	/**
	 * the data to be updated
	 *
	 * @param array $data
	 *
	 * @return UpdateSQL
	 */
	public function set($data) {
		$this->data += $data;

		return $this;
	}

	public function count() {
		if (empty ($this->from)) {
			$this->error = 'no table specified!';

			return false;
		}
		$this->checkDialect();
		$values    = new BindValues ();
		$froms     = $this->prepareFrom($this->sanitize($this->from));
		$sql       = $this->dialect->getUpdateSQL($froms [0] [0], $this->data, $this->where, $values);
		$this->sql = $sql;
		if ($sql) {
			try {
				$statement = $this->dialect->prepare($sql);
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
				$cnt = false;
				if ($rst) {
					$cnt = $statement->rowCount();
				} else {
					$this->dumpSQL($statement);
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
