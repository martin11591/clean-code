<?php

namespace app\core\middlewares;

class SessionMiddleware extends BaseMiddleware {
    public function __construct($action = null)
    {
        $this->action = $action;
        $this->args = func_get_args();
        array_shift($this->args);
        return $this;
    }

    public function execute()
    {
        $this->pair = func_get_args();
        if (isset($this->pair[0])) $this->pair = $this->pair[0];
        if ($this->action && method_exists($this, $this->action)) {
            call_user_func_array([$this, $this->action], $this->args);
        }
        return $this->pair;
    }
}