<?php

namespace app\core\traits;

use app\core\Application;
use app\core\middlewares\BaseMiddleware;

trait TraitMiddleware {
    private function registerMiddlewareToContainer(&$container, $middleware)
    {
        if ($middleware instanceof BaseMiddleware || (is_callable($middleware) && $middleware instanceof \Closure) || (is_array($middleware) && class_exists($middleware[0]))) {
            $container[] = $middleware;

        } else throw new \Exception("Cannot register Middleware for session - invalid callback");
        return $this;
    }

    private function callMiddlewares($middlewares)
    {
        $args = func_get_args();
        array_shift($args);
        foreach ($middlewares as $middleware) {
            // $middleware = \Closure::bind($middleware, Application::$app);
            if ($middleware instanceof BaseMiddleware) {
                $pair = $middleware->execute($pair);
            } else if (is_callable($middleware) && $middleware instanceof \Closure) {
                // $middleware->call(Application::$app, $pair);
                $middleware = $middleware->bindTo(Application::$app);
                call_user_func_array($middleware, $args);
            }
        }
    }
}