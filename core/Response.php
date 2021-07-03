<?php

namespace app\core;

class Response {
    public function __construct()
    {
        ob_start();
        return $this;
    }

    public function __destruct()
    {
        while (ob_get_level() > 0) ob_end_flush();
    }

    public function send()
    {
        $this->__destruct();
    }
}