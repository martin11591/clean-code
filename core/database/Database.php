<?php

namespace app\core\database;

class Database {
    protected $driver;
    protected $connection;
    protected $dbClass;
    public $dsn;
    public $username;
    public $password;
    public $PDOOptions;

    public function __construct($dsn = null, $username = null, $password = null, $PDOOptions = [])
    {
        if ($dsn) {
            $this->dsn = $dsn;
            $this->username = $username;
            $this->password = $password;
            $this->PDOOptions = $PDOOptions;
            return $this->connect();
        }
        return $this;
    }

    public function connect()
    {
        if (!$this->dsn) return null;
        if ($this->dbClass) return $this->dbClass;
        if (!$this->connection) {
            $this->connection = new \PDO($this->dsn, $this->username, $this->password, $this->PDOOptions);
            $this->driver = $this->connection->getAttribute(\PDO::ATTR_DRIVER_NAME);
            $this->connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $driverClass = __NAMESPACE__ . '\\' . ucfirst($this->driver[0]) . substr($this->driver, 1) . 'DriverDatabase';
            $this->dbClass = new $driverClass($this->connection);
        }
        return $this->dbClass;
    }

    public function close()
    {
        $this->connection = null;
        return true;
    }
}