<?php

namespace app\core\middlewares;

class SessionMiddleware extends BaseMiddleware {
    public function __construct($action)
    {
        $this->action = $action;
        $this->args = func_get_args();
        array_shift($this->args);
        return $this;
    }

    public function execute()
    {
        $this->pair = func_get_args()[0];
        if (method_exists($this, $this->action)) {
            call_user_func_array([$this, $this->action], $this->args);
        }
        return $this->pair;
    }

    public function test()
    {
        $this->pair->key .= '5';
    }
}