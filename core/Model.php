<?php

namespace app\core;

abstract class Model {
    public function load($data)
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->{$key} = html_entity_decode($value, ENT_QUOTES, 'UTF-8');
            }
        }
    }
}