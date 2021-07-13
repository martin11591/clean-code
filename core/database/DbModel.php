<?php

namespace app\core\database;

use app\core\Application;
use app\core\Model;

abstract class DbModel extends Model {
    private static $fields;
    private static $primaryKey;

    public function tableName()
    {
        return '';
    }

    public function primaryKey()
    {
        if (isset(self::$primaryKey)) return self::$primaryKey;
        self::$primaryKey = Application::$app->dbh->getPrimaryKeys($this->tableName());
        if (is_array(self::$primaryKey)) self::$primaryKey = self::$primaryKey[0];
        return self::$primaryKey;
    }

    public function fields()
    {
        if (isset(self::$fields)) return self::$fields;
        self::$fields = Application::$app->dbh->getColumns($this->tableName());
        return self::$fields;
    }

    public function specifiedFields($fields = [])
    {
        if (!$fields || is_array($fields) && empty($fields)) return $this->fields();
        if (!is_array($fields)) $fields = [$fields];
        $columns = $this->fields();
        return array_values(array_intersect($fields, $columns));
    }

    public function load($data)
    {
        foreach ($data as $key => $value) {
            if ($this->fieldAllowed($key)) {
                $this->{$key} = html_entity_decode($value, ENT_QUOTES, 'UTF-8');
            }
        }
        return $this;
    }

    private function fieldAllowed($name)
    {
        $fields = $this->fields();
        if ($fields == "*") return true;
        if (!is_array($fields)) $fields = [$fields];
        if (in_array("*", $fields) || in_array($name, $fields)) {
            return true;
         } else {
             return false;
         }
    }

    public function insert($doNotRemovePrimaryKey = false)
    {
        if (!$this->fields()) return null;
        $fields = $this->fields();
        $primaryKey = $this->primaryKey();
        $values = $this->values();
        if ($doNotRemovePrimaryKey !== true) {
            $fields = array_values(array_diff($fields, [$primaryKey]));
            unset($values[$primaryKey]);
        }
        return Application::$app->dbh->insert($this->tableName(), $fields, $values);
    }

    public function put()
    {
        if (!$this->fields()) return null;
        return Application::$app->dbh->insertUpdate($this->tableName(), $this->fields(), $this->values());
    }

    public function update($fields = [], $primaryKey = null, $conditionals = "")
    {
        $fields = $this->specifiedFields($fields);
        if (!$primaryKey) $primaryKey = $this->primaryKey();
        if (is_array($primaryKey) && count($primaryKey) > 1) $primaryKey = $primaryKey[0];
        if (!in_array($primaryKey, $this->fields()) || !isset($this->{$primaryKey})) return null;
        $fields = array_values(array_diff($fields, [$primaryKey]));
        $query = rtrim("WHERE `{$primaryKey}` = " . $this->{$primaryKey} . " " . trim($conditionals, " "), " ");
        return Application::$app->dbh->update($this->tableName(), $fields, $this->values($fields), $query);
    }

    public function delete($primaryKey = null)
    {
        if (!$primaryKey) $primaryKey = $this->primaryKey();
        if (is_array($primaryKey) && count($primaryKey) > 1) $primaryKey = $primaryKey[0];
        if (!in_array($primaryKey, $this->fields()) || !isset($this->{$primaryKey})) return null;
        var_dump($primaryKey, $this);
        return Application::$app->dbh->delete($this->tableName(), "{$primaryKey} = " . $this->{$primaryKey});
    }

    private function values($fields = [])
    {
        $values = [];
        foreach ($this->specifiedFields($fields) as $key) {
            $values[$key] = isset($this->{$key}) ? $this->{$key} : null;
        }
        return $values;
    }

    public function find($data = [], $join = "OR", $limit = false)
    {
        $join = trim(strtoupper($join), " ");
        if ($join !== "AND") $join = "OR";
        $columns = Application::$app->dbh->getColumns($this->tableName());
        foreach ($data as $key => $value) {
            if (!in_array($key, $columns)) unset($data[$key]);
        }
        $query = "SELECT * FROM {$this->tableName()} WHERE ";
        foreach ($data as $key => $value) {
            $query .= "`{$key}` = :{$key} {$join} ";
        }
        $query = substr($query, 0, strlen($join) * -1 - 2) . ($limit !== false ? " LIMIT {$limit}" : "");
        $stmt = Application::$app->dbh->prepare($query);
        $stmt->execute($data);
        $class = get_class($this);
        $result = $stmt->fetchAll(\PDO::FETCH_CLASS, $class);
        if (empty($result)) {
            return false;
        } else {
            return $result;
        }
    }

    public function findExact($data = [])
    {
        return $this->find($data, "AND", false);
    }

    public function findAny($data = [])
    {
        return $this->find($data, "OR", false);
    }

    public function findOneExact($data = [])
    {
        $result = $this->find($data, "AND", 1);
        if ($result) $result = $result[0];
        return $result;
    }

    public function findOneAny($data = [])
    {
        $result = $this->find($data, "OR", 1);
        if ($result) $result = $result[0];
        return $result;
    }

    public function getAll($fields = "*", $perPage = false, $page = 1)
    {
        if ($fields == "*") {
            $fields = Application::$app->dbh->getColumns($this->tableName());
        } else {
            $fields = $this->specifiedFields($fields);
        }
        $fields = implode(", ", array_map(function($field) {
            return "`{$field}`";
        }, $fields));
        $query = "SELECT {$fields} FROM {$this->tableName()}";
        if ($perPage !== false) {
            $perPage = intval($perPage);
            if ($perPage < 1) $perPage = 1;
            $page = intval($page);
            if ($page < 1) $page = 1;
            $query .= " LIMIT {$perPage} OFFSET " . ($page - 1) * $perPage;
        }
        $stmt = Application::$app->dbh->query($query);
        $class = get_class($this);
        $result = $stmt->fetchAll(\PDO::FETCH_CLASS, $class);
        return $result;
    }

    public function getColumn($column, $where = false)
    {
        $stmt = Application::$app->dbh->query("SELECT `{$column}` FROM {$this->tableName()}" . ($where !== false ? " {$where}" : ""));
        $result = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        return $result;
    }
}