<?php

namespace app\core;

class Database {
    protected $driver;
    protected $connection;
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
        if ($this->connection) return $this->connection;
        $this->connection = new \PDO($this->dsn, $this->username, $this->password, $this->PDOOptions);
        $this->driver = $this->connection->getAttribute(\PDO::ATTR_DRIVER_NAME);
        return $this->connection;
    }

    public function close()
    {
        $this->connection = null;
        return true;
    }
}