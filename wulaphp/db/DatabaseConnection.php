<?php
namespace wulaphp\db;

class DatabaseConnection {
    
    /**
     * start a database transaction
     *
     * @param string $name database or config
     * @return boolean
     */
    function start() {
        $dialect = DatabaseDialect::getDialect ( $name );
        try {
            return $dialect->beginTransaction ();
        } catch ( \Exception $e ) {
            DatabaseDialect::$lastErrorMassge = $e->getMessage ();
            return false;
        }
    }

    /**
     * commit a transaction
     *
     * @param string $name database or config
     */
    function commit() {
        $dialect = DatabaseDialect::getDialect ( $name );
        try {
            return $dialect->commit ();
        } catch ( PDOException $e ) {
            DatabaseDialect::$lastErrorMassge = $e->getMessage ();
            return false;
        }
    }

    /**
     * rollback a transaction
     *
     * @param string $name database or config
     */
    function rollback() {
        $dialect = DatabaseDialect::getDialect ( $name );
        try {
            return $dialect->rollBack ();
        } catch ( PDOException $e ) {
            DatabaseDialect::$lastErrorMassge = $e->getMessage ();
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
        return new InsertSQL ( $datas, $batch );
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
        return new SaveQuery ( $data, $where, $idf );
    }

    /**
     * shortcut for new Query
     *
     * @param string $fields
     * @return Query
     */
    function select($fields = '*') {
        return new Query ( func_get_args () );
    }

    /**
     * 锁定表.
     *
     * @param string $table
     */
    function lock($table, $dialect = null) {
        if ($dialect == null) {
            $dialect = DatabaseDialect::getDialect ();
        }
        $table = $dialect->getTableName ( $table );
        $dialect->query ( "LOCK TABLES `" . $table . "` " );
    }

    function unlock($dialect = null) {
        if ($dialect == null) {
            $dialect = DatabaseDialect::getDialect ();
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
        return new UpdateSQL ( $table );
    }

    /**
     * delete data from table(s)
     *
     * @return DeleteSQL
     */
    function delete() {
        return new DeleteSQL ( func_get_args () );
    }

    /**
     * execute a ddl SQL.
     *
     * @param string $sql
     * @param mixed $name
     * @return mixed
     */
    function exec($sql, $name = null) {
        $dialect = DatabaseDialect::getDialect ( $name );
        if (is_null ( $dialect )) {
            return false;
        }
        try {
            $dialect->exec ( $sql );
        } catch ( Exception $e ) {
            DatabaseDialect::$lastErrorMassge = $e->getMessage ();
            return false;
        }
        return true;
    }

    function query($sql, $name = null) {
        $dialect = DatabaseDialect::getDialect ( $name );
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
            DatabaseDialect::$lastErrorMassge = $e->getMessage ();
        }
        return null;
    }
}