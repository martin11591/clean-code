<?php

namespace app\core\database;

use app\core\traits\DriverDatabaseTrait;

class SqliteDriverDatabase extends DriverDatabase implements DriverDatabaseInterface {
    private static $tables;
    private static $columns = [];
    private static $primaryKeys = [];

    protected function getTables($cached = true)
    {
        if ($cached === true && isset(self::$tables)) return self::$tables;
        $stmt = $this->handler->prepare("SELECT name FROM sqlite_master WHERE type = 'table'");
        $stmt->execute();
        $result = [];
        while (($row = $stmt->fetch(\PDO::FETCH_ASSOC))) {
            $result[] = $row['name'];
        };
        self::$tables = $result;
        return $result;
    }

    protected function getColumns($table, $cached = true)
    {
        if ($cached === true && isset(self::$columns[$table])) return self::$columns[$table];
        $stmt = $this->handler->prepare("PRAGMA table_info({$table})");
        $stmt->execute();
        $result = [];
        while (($row = $stmt->fetch(\PDO::FETCH_ASSOC))) {
            $result[] = $row['name'];
        };
        self::$columns[$table] = $result;
        return $result;
    }

    protected function getPrimaryKeys($table, $cached = true)
    {
        if ($cached === true && isset(self::$primaryKeys[$table])) return self::$primaryKeys[$table];
        $stmt = $this->handler->prepare("PRAGMA table_info({$table})");
        $stmt->execute();
        $result = [];
        while (($row = $stmt->fetch(\PDO::FETCH_ASSOC))) {
            if ($row['pk'] === '1') $result[] = $row['name'];
        };
        self::$primaryKeys[$table] = $result;
        return $result;
    }

    public function dump()
    {
        return '';
    }

    public function insert($table, $fields, $values)
    {
        $values = array_slice(func_get_args(), 2);
        $queryColumns = $this->prepareColumns($fields, true);
        $queryValues = $this->prepareValues($fields);
        if ($this->transactionEnabled()) $this->handler->beginTransaction();
        $stmt = $this->handler->prepare("INSERT INTO `{$table}` {$queryColumns} VALUES {$queryValues}");
        $this->executeStatements($stmt, $values);
    }

    public function insertReplace($table, $fields, $values)
    {
        $values = array_slice(func_get_args(), 2);
        $queryColumns = $this->prepareColumns($fields, true);
        $queryValues = $this->prepareValues($fields);
        if ($this->transactionEnabled()) $this->handler->beginTransaction();
        $stmt = $this->handler->prepare("REPLACE INTO `{$table}` {$queryColumns} VALUES {$queryValues}");
        $this->executeStatements($stmt, $values);
    }

    public function insertUpdate($table, $fields, $values)
    {
        $this->insertReplace($table, $fields, $values);
    }

    use DriverDatabaseTrait;
}