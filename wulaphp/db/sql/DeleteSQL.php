<?php
namespace wulaphp\db\sql;

/**
 * delete SQL
 *
 * @author guangfeng.ning
 *
 */
class DeleteSQL extends QueryBuilder {

    private $using = array ();

    /**
     * mysql and postgresql support using syntax, others may not support.
     *
     * @param string $using
     * @return DeleteSQL
     * @deprecated
     *
     */
    public function using($using) {
        $this->using [] = self::parseAs ( $using );
        return $this;
    }
    
    /**
     * perform the delete sql, false for deleting failed.
     * Just call count() function for short.
     *
     * @return int
     */
    public function count() {
        if (empty ( $this->from )) {
            $this->error = 'no table specified!';
            return false;
        }
        $this->checkDialect ();
        $values = new BindValues ();
        $from = $this->prepareFrom ( $this->sanitize ( $this->from ) );
        $using = $this->prepareFrom ( $this->sanitize ( $this->using ) );
        $sql = $this->dialect->getDeleteSQL ( $from [0], $using, $this->where, $values );
        if ($sql) {
            try {
                $statement = $this->dialect->prepare ( $sql );
                foreach ( $values as $value ) {
                    list ( $name, $val, $type ) = $value;
                    if (! $statement->bindValue ( $name, $val, $type )) {
                        $this->errorSQL = $sql;
                        $this->errorValues = $values->__toString ();
                        $this->error = 'can not bind the value ' . $val . '[' . $type . '] to the argument:' . $name;
                        log_error ( $this->error . ' [' . $this->errorSQL . ']' );
                        return false;
                    }
                }
                $rst = $statement->execute ();
                $cnt = false;
                if ($rst) {
                    $cnt = $statement->rowCount ();
                }
                if ($statement) {
                    $statement->closeCursor ();
                    $statement = null;
                }
                QueryBuilder::addSqlCount ();
                return $cnt;
            } catch ( \PDOException $e ) {
                $this->error = $e->getMessage ();
                $this->errorSQL = $sql;
                $this->errorValues = $values->__toString ();
            }
        } else {
            $this->error = 'Can not generate the delete SQL';
            $this->errorSQL = '';
            $this->errorValues = $values->__toString ();
        }
        if ($this->error) {
            log_error ( $this->error . ' [' . $this->errorSQL . ']' );
        }
        return false;
    }
}
