<?php

namespace app\core;

abstract class Model {
    private $fields = "*";

    public function load($data)
    {
        foreach ($data as $key => $value)
            if ($this->fieldAllowed($key)) $this->{$key} = html_entity_decode($value, ENT_QUOTES, 'UTF-8');
    }

    private function fieldAllowed($name)
    {
        if (!is_array($this->fields)) $this->fields = [$this->fields];
        if (in_array("*", $this->fields) || in_array($name, $this->fields)) return true;
        return false;
    }
}