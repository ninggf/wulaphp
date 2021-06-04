<?php

namespace wulaphp\db\sql;

use wulaphp\db\dialect\DatabaseDialect;

class Procedure {
    private $dialect;
    private $procedure;
    private $args = [];
    private $outs = [];

    public function __construct(DatabaseDialect $dialect, string $procedure) {
        $this->dialect   = $dialect;
        $this->procedure = $procedure;
    }

    public function in($value, int $type = \PDO::PARAM_STR): Procedure {
        $this->args[] = [$value, $type];

        return $this;
    }

    public function call(?string &$error = null): ?\PDOStatement {
        $sql = $this->dialect->getProcedureSQL($this->procedure, count($this->args));
        try {
            $stmt = $this->dialect->prepare($sql);
            foreach ($this->args as $i => $arg) {
                [$value, $type] = $arg;
                $stmt->bindValue($i + 1, $value, $type);
            }

            $rst = $stmt->execute();

            return $rst ? $stmt : null;
        } catch (\Exception $e) {
            $error = $e->getMessage();
        }

        return null;
    }
}