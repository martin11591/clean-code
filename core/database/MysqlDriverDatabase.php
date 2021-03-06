<?php

namespace app\core\database;

use app\core\Application;
use app\core\traits\DriverDatabaseTrait;

class MysqlDriverDatabase extends DriverDatabase implements DriverDatabaseInterface {
    private static $dbName;
    private static $tables;
    private static $columns = [];
    private static $primaryKeys = [];

    protected function getDBName()
    {
        if (isset(self::$dbName)) return self::$dbName;
        $stmt = $this->handler->prepare("SELECT DATABASE()");
        $stmt->execute();
        self::$dbName = $stmt->fetchColumn();
        return self::$dbName;
    }

    protected function getTables($cached = true)
    {
        if ($cached === true && isset(self::$tables)) return self::$tables;
        $dbName = $this->getDBName();
        $stmt = $this->handler->prepare("SELECT `table_name` FROM `information_schema`.`tables` WHERE `table_schema` = '{$dbName}'");
        $stmt->execute();
        $result = [];
        while (($row = $stmt->fetch(\PDO::FETCH_ASSOC))) {
            $result[] = $row['table_name'];
        };
        self::$tables = $result;
        return $result;
    }

    protected function getColumns($table, $cached = true)
    {
        if ($cached === true && isset(self::$columns[$table])) return self::$columns[$table];
        $dbName = $this->getDBName();
        $stmt = $this->handler->prepare("SELECT `column_name` FROM `information_schema`.`columns` WHERE `table_schema` = '{$dbName}' AND table_name = '{$table}'");
        $stmt->execute();
        $result = [];
        while (($row = $stmt->fetch(\PDO::FETCH_ASSOC))) {
            $result[] = $row['column_name'];
        };
        self::$columns[$table] = $result;
        return $result;
    }

    protected function getPrimaryKeys($table, $cached = true)
    {
        if ($cached === true && isset(self::$primaryKeys[$table])) return self::$primaryKeys[$table];
        $dbName = $this->getDBName();
        $stmt = $this->handler->prepare("SELECT `column_name` FROM `information_schema`.`key_column_usage` WHERE `table_schema` = '{$dbName}' AND `table_name` = '{$table}' AND `constraint_name` = 'PRIMARY'");
        $stmt->execute();
        $result = [];
        while (($row = $stmt->fetch(\PDO::FETCH_ASSOC))) {
            $result[] = $row['column_name'];
        };
        self::$primaryKeys[$table] = $result;
        return $result;
    }

    public function insert($table, $fields, $values)
    {
        $values = array_slice(func_get_args(), 2);
        $queryColumns = $this->prepareColumns($fields, true);
        $queryValues = $this->prepareValues($fields);
        if ($this->transactionEnabled()) $this->handler->beginTransaction();
        $stmt = $this->handler->prepare("INSERT INTO `{$table}` {$queryColumns} VALUES {$queryValues}");
        return $this->executeStatements($stmt, $values);
    }

    public function insertReplace($table, $fields, $values)
    {
        $values = array_slice(func_get_args(), 2);
        $queryColumns = $this->prepareColumns($fields, true);
        $queryValues = $this->prepareValues($fields);
        if ($this->transactionEnabled()) $this->handler->beginTransaction();
        $stmt = $this->handler->prepare("REPLACE INTO `{$table}` {$queryColumns} VALUES {$queryValues}");
        return $this->executeStatements($stmt, $values);
    }

    public function insertUpdate($table, $fields, $values)
    {
        $values = array_slice(func_get_args(), 2);
        $queryColumns = $this->prepareColumns($fields, true);
        $queryValues = $this->prepareValues($fields);
        if ($this->transactionEnabled()) $this->handler->beginTransaction();
        $stmt = $this->handler->prepare("INSERT INTO `{$table}` {$queryColumns} VALUES {$queryValues} ON DUPLICATE KEY UPDATE");
        return $this->executeStatements($stmt, $values);
    }

    use DriverDatabaseTrait;
}