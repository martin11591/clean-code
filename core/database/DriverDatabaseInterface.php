<?php

namespace app\core\database;

interface DriverDatabaseInterface {
    public function __construct(\PDO $handler);
    // public function getTables();
    // public function getColumns($table);
    // public function getPrimaryKeys($table);
}