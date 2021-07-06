<?php

namespace app\core;

use app\core\traits\TraitMiddleware;

class Controller {
    use TraitMiddleware;

    private $middlewares = [];

    public function registerMiddleware($middleware)
    {
        return $this->registerMiddlewareToContainer($this->middlewares, $middleware);
    }

    public function registerMiddlewares()
    {
        foreach (func_get_args() as $middleware) $this->registerMiddleware($middleware);
        return $this;
    }

    public function getMiddlewares()
    {
        return $this->middlewares;
    }
}