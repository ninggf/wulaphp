<?php
namespace wulaphp\db;

use wulaphp\db\dialect\DatabaseDialect;
use wulaphp\db\sql\InsertSQL;
use wulaphp\db\sql\SaveQuery;
use wulaphp\db\sql\UpdateSQL;
use wulaphp\db\sql\DeleteSQL;
use wulaphp\db\sql\Query;

class DatabaseConnection {

    private $dialect = null;

    public $error = null;

    public function __construct($dialect) {
        if (! $dialect instanceof DatabaseDialect) {
            trigger_error ( 'the dialect is not instance of DatabaseDialect', E_USER_ERROR );
        }
        $this->dialect = $dialect;
    }

    /**
     *
     * @return \wulaphp\db\dialect\DatabaseDialect
     */
    public function getDialect() {
        return $this->dialect;
    }

    /**
     * start a database transaction
     *
     * @param string $name database or config
     * @return boolean
     */
    function start() {
        $dialect = $this->dialect;
        try {
            return $dialect->beginTransaction ();
        } catch ( \Exception $e ) {
            $this->error = $e->getMessage ();
            return false;
        }
    }

    /**
     * commit a transaction
     *
     * @param string $name database or config
     */
    function commit() {
        $dialect = $this->dialect;
        try {
            return $dialect->commit ();
        } catch ( \PDOException $e ) {
            $this->error = $e->getMessage ();
            return false;
        }
    }

    /**
     * rollback a transaction
     *
     * @param string $name database or config
     */
    function rollback() {
        $dialect = $this->dialect;
        try {
            return $dialect->rollBack ();
        } catch ( \PDOException $e ) {
            $this->error = $e->getMessage ();
            return false;
        }
    }

    /**
     * insert data into table
     *
     * @param array $datas
     * @param array $batch
     */
    function insert($datas, $batch = false) {
        $sql = new InsertSQL ( $datas, $batch );
        $sql->setDb ( $this );
        return $sql;
    }

    /**
     * insert or update a record recording to the $where or id value.
     *
     * @param array $data
     * @param array $where
     * @param string $idf
     * @return SaveQuery
     */
    function save($data, $where, $idf = 'id') {
        $sql = new SaveQuery ( $data, $where, $idf );
        $sql->setDb ( $this );
        return $sql;
    }

    /**
     * shortcut for new Query
     *
     * @param string $fields
     * @return Query
     */
    function select($fields = '*') {
        $args = func_get_args ();
        if (! $args) {
            $args = '*';
        }
        $sql = new Query ( $args );
        $sql->setDb ( $this );
        return $sql;
    }

    /**
     * 锁定表.
     *
     * @param string $table
     */
    function lock($table, $dialect = null) {
        if ($dialect == null) {
            $dialect = $this->dialect;
        }
        $table = $dialect->getTableName ( $table );
        $dialect->query ( "LOCK TABLES `" . $table . "` " );
    }

    function unlock($dialect = null) {
        if ($dialect == null) {
            $dialect = $this->dialect;
        }
        $dialect->query ( "UNLOCK TABLES" );
    }

    /**
     * update data
     *
     * @param string $table
     * @return UpdateSQL
     */
    function update($table) {
        $sql = new UpdateSQL ( $table );
        $sql->setDb ( $this );
        return $sql;
    }

    /**
     * delete data from table(s)
     *
     * @return DeleteSQL
     */
    function delete() {
        $sql = new DeleteSQL ( func_get_args () );
        $sql->setDb ( $this );
        return $sql;
    }

    /**
     * execute a ddl SQL.
     *
     * @param string $sql
     * @param mixed $name
     * @return mixed
     */
    function exec($sql, $name = null) {
        $dialect = $this->dialect;
        if (is_null ( $dialect )) {
            return false;
        }
        try {
            $dialect->exec ( $sql );
        } catch ( \Exception $e ) {
            $this->error = $e->getMessage ();
            return false;
        }
        return true;
    }

    function query($sql, $name = null) {
        $dialect = $this->dialect;
        if (is_null ( $dialect )) {
            return null;
        }
        try {
            $options [\PDO::ATTR_CURSOR] = \PDO::CURSOR_SCROLL;
            $statement = $dialect->prepare ( $sql, $options );
            $rst = $statement->execute ();
            if ($rst) {
                $result = $statement->fetchAll ( \PDO::FETCH_ASSOC );
                return $result;
            }
        } catch ( \Exception $e ) {
            $this->error = $e->getMessage ();
        }
        return null;
    }
}