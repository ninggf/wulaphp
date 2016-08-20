<?php
namespace wulaphp\db\sql;

class SaveQuery extends QueryBuilder {

    private $intoTable;

    private $condition;

    private $data;

    private $idf = null;

    public function __construct($data, $where = false, $idf = 'id') {
        parent::__construct ();
        $this->condition = $where;
        $this->data = $data;
        if ($idf) {
            $this->idf = $idf;
        }
    }

    public function into($table) {
        $this->intoTable = $table;
        return $this;
    }

    public function count() {
        $rst = $this->save ();
        if ($rst) {
            return 1;
        }
        return 0;
    }

    private function save() {
        if (empty ( $this->intoTable )) {
            $this->error = 'no table specified!';
            return false;
        }
        if (empty ( $this->data )) {
            $this->error = 'no data!';
            return false;
        }
        $id = empty ( $this->idf ) ? '*' : $this->idf;
        $insert = true;
        $dialect = $this->getDialect ();
        if (empty ( $this->data [$id] )) {
            unset ( $this->data [$id] );
        }
        if ($this->condition) {
            $insert = $this->db->select ()
                ->setDialect ( $dialect )
                ->from ( $this->intoTable )
                ->where ( $this->condition )
                ->exist ( $id );
        } else if ($this->data [$id]) {
            $this->condition [$id] = $this->data [$id];
            $insert = false;
        }
        if ($insert) {
            $ids = $this->db->insert ( $this->data )
                ->setDialect ( $dialect )
                ->into ( $this->intoTable )
                ->exec ();
            if ($ids) {
                if ($this->idf && ! empty ( $ids [0] )) {
                    $this->data [$this->idf] = $ids [0];
                }
                return $this->data;
            }
        } else if ($this->db->update ( $this->intoTable )
            ->setDialect ( $dialect )
            ->set ( $this->data )
            ->where ( $this->condition )
            ->exec ()) {
            return $this->data;
        }
        return false;
    }
}