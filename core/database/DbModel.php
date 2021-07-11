<?php

namespace app\core\database;

use app\core\Application;
use app\core\Model;

abstract class DbModel extends Model {
    private static $fields;

    public function tableName()
    {
        return '';
    }

    public function primaryKey()
    {
        return '';
    }

    public function fields()
    {
        if (isset(self::$fields)) return self::$fields;
        self::$fields = Application::$app->dbh->getColumns($this->tableName());
        return self::$fields;
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

    public function insert()
    {
        if (!$this->fields()) return null;
        return Application::$app->dbh->insert($this->tableName(), $this->fields(), $this->values());
    }

    private function values()
    {
        $values = [];
        foreach ($this->fields() as $key) {
            $values[$key] = isset($this->{$key}) ? $this->{$key} : null;
        }
        return $values;
    }
}