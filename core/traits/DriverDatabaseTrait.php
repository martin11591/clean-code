<?php

namespace app\core\traits;

trait DriverDatabaseTrait {
    public function delete($table, $conditionals)
    {
        if ($this->transactionEnabled()) $this->handler->beginTransaction();
        $stmt = $this->handler->prepare("DELETE FROM `{$table}` WHERE {$conditionals}");
        $stmt->execute();
        if ($this->transactionEnabled()) {
            $this->tryCommit();
        }
        return true;
    }

    public function update($table, $fields, $values, $conditionals = "")
    {
        $querySet = $this->prepareUpdateSet($fields);
        $queryValues = $this->prepareUpdateValues($fields, $values);
        if ($this->transactionEnabled()) $this->handler->beginTransaction();
        $query = rtrim("UPDATE `{$table}` SET {$querySet} {$conditionals}", " ");
        $stmt = $this->handler->prepare($query);
        $stmt->execute($queryValues);
        if ($this->transactionEnabled()) {
            $this->tryCommit();
        }
        return true;
    }
}