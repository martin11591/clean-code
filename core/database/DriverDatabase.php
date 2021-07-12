<?php

namespace app\core\database;

use app\core\Helpers;

abstract class DriverDatabase implements DriverDatabaseInterface {
    const NO_TRANSACTION = 0;
    const SINGLE_TRANSACTION = 1;
    const FULL_TRANSACTION = 2;

    protected $handler = null;
    private $transactionMode = self::NO_TRANSACTION;

    public function __construct(\PDO $handler)
    {
        $this->handler = $handler;
        return $this;
    }

    public function __call($name, $arguments)
    {
        if (!method_exists($this, $name)) {
            return call_user_func_array([$this->handler, $name], $arguments);
        } else {
            return call_user_func_array([$this, $name], $arguments);
        }
    }

    public function setTransactionMode($mode = self::NO_TRANSACTION)
    {
        if ($mode === self::FULL_TRANSACTION) $this->transactionMode = self::FULL_TRANSACTION;
        else if ($mode === self::SINGLE_TRANSACTION) $this->transactionMode = self::SINGLE_TRANSACTION;
        else $this->transactionMode = self::NO_TRANSACTION;
        return $this;
    }

    public function transactionEnabled()
    {
        if ($this->transactionMode === self::FULL_TRANSACTION || $this->transactionMode === self::SINGLE_TRANSACTION) return true;
        return false;
    }

    public function isSingleTransaction()
    {
        if ($this->transactionMode === self::SINGLE_TRANSACTION) return true;
        return false;
    }

    private function tryCommit()
    {
        try {
            $this->handler->commit();
        } catch (\PDOException $e)
        {
            $this->handler->rollBack();
            return $e;
        }
        return true;
    }

    private function executeStatements($stmt, $values)
    {
        foreach ($values as $row) {
            $stmt->execute(array_values($row));
            if ($this->isSingleTransaction()) {
                $this->tryCommit();
            }
        }
        if (!$this->isSingleTransaction() && $this->transactionEnabled()) {
            $this->tryCommit();
        }
        return true;
    }

    private function escapeColumnName($name)
    {
        return "`{$name}`";
    }

    private function escapeColumnNames($names)
    {
        $names = Helpers::flattenArray(func_get_args());
        foreach ($names as &$name) {
            $name = $this->escapeColumnName($name);
        }
        return $names;
    }

    private function prepareColumns($fields)
    {
        $queryColumns = $this->escapeColumnNames($fields);
        $queryColumns = "(" . implode(", ", $queryColumns) . ")";
        return $queryColumns;
    }

    private function prepareValues($fields, $named = false)
    {
        $queryValues = "";
        if ($named === true) {
            foreach ($fields as $field) {
                $queryValues .= ":{$field}, ";
            }
        } else {
            $queryValues = str_repeat("?, ", count($fields));
        }
        $queryValues = substr($queryValues, 0, -2);
        $queryValues = "({$queryValues})";
        return $queryValues;
    }

    private function prepareUpdateSet($fields, $named = false)
    {
        $querySet = "";
        foreach ($fields as $field) {
            if ($named === true) {
                $querySet .= "`{$field}` = :{$field}, ";
            } else {
                $querySet .= "`{$field}` = ?, ";
            }
        }
        $querySet = substr($querySet, 0, -2);
        return $querySet;
    }

    private function prepareUpdateValues($fields, $values, $named = false)
    {
        $valuesArray = [];
        foreach ($fields as $field) {
            if ($named === true) {
                $valuesArray[":{$field}"] = $values[$field];
            } else {
                $valuesArray[] = $values[$field];
            }
        }
        return $valuesArray;
    }
}