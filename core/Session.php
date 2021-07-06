<?php

namespace app\core;

use app\core\Application;
use app\core\middlewares\BaseMiddleware;

class Session {
    const NON_EXISTENT_AS_NULL = 'nonexistent';
    const OVERWRITE = 'overwrite';
    const NO_OVERWRITE = 'no-overwrite';
    public static $nonExistentThrowsError = false;
    public static $overwrite = self::OVERWRITE;

    private $instance = null;
    private $middlewares = [
        "before" => [
            "get" => [],
            "set" => []
        ],
        "after" => [
            "get" => [],
            "set" => []
        ],
    ];

    public function __construct($name = "")
    {
        if (session_name() === "PHPSESSID") session_name($_ENV['APP_NAME']);
        if (session_status() === PHP_SESSION_DISABLED) throw new \Exception("Sessions are disabled!");
        if (session_status() === PHP_SESSION_NONE) session_start();
        $this->create($name);
        return $this;
    }

    public function create($name = "")
    {
        if ($name === "") $name = $_ENV['APP_NAME'];
        if (!isset($_SESSION[$name])) $_SESSION[$name] = [];
        $this->instance = &$_SESSION[$name];
        return $this;
    }

    public function get($key = null)
    {
        $pair = $this->keyAndValueToObject($key);
        $this->callMiddlewares($this->middlewares["before"]["get"], $pair);
        if ($pair->key === null) return $this->instance;
        if ($this->existsThrow($pair->key)) {
            $pair->value = $this->instance[$pair->key];
            $this->callMiddlewares($this->middlewares["after"]["get"], $pair);
            return $pair->value;
        } else return null;
    }

    public function set($key, $value = null)
    {
        if (self::$overwrite === self::OVERWRITE || (self::$overwrite === self::NO_OVERWRITE) && !$this->exists($key)) {
            $pair = $this->keyAndValueToObject($key, $value);
            $this->callMiddlewares($this->middlewares["before"]["set"], $pair);
            $this->instance[$pair->key] = $pair->value;
            $this->callMiddlewares($this->middlewares["after"]["set"], $pair);
            $this->instance[$pair->key] = $pair->value;
        }
        return $this;
    }

    private function keyAndValueToObject($key = null, $value = null)
    {
        $pair = new \stdClass();
        $pair->key = $key;
        $pair->value = $value;
        return $pair;
    }

    private function callMiddlewares($middlewares, &$pair)
    {
        foreach ($middlewares as $middleware) {
            // $middleware = \Closure::bind($middleware, Application::$app);
            if ($middleware instanceof BaseMiddleware) {
                $pair = $middleware->execute($pair);
            } else if (is_callable($middleware) && $middleware instanceof \Closure) {
                $middleware->call(Application::$app, $pair);
            }
        }
    }

    public function delete($key)
    {
        if ($this->exists($key)) unset($this->instance[$key]);
        return $this;
    }

    public function exists($key)
    {
        return isset($this->instance[$key]);
    }

    public function existsThrow($key)
    {
        if (!$this->exists($key) && self::$nonExistentThrowsError === self::NON_EXISTENT_AS_NULL) throw new \Exception("Session key \"{$key}\" not exists!");
        return $this->exists($key);
    }

    public function registerBeforeGetMiddleware($middleware)
    {
        return $this->registerMiddleware($this->middlewares["before"]["get"], $middleware);
    }

    public function registerAfterGetMiddleware($middleware)
    {
        return $this->registerMiddleware($this->middlewares["after"]["get"], $middleware);
    }

    public function registerBeforeSetMiddleware($middleware)
    {
        return $this->registerMiddleware($this->middlewares["before"]["set"], $middleware);
    }

    public function registerAfterSetMiddleware($middleware)
    {
        return $this->registerMiddleware($this->middlewares["after"]["set"], $middleware);
    }

    private function registerMiddleware(&$container, $middleware)
    {
        if ($middleware instanceof BaseMiddleware || (is_callable($middleware) && $middleware instanceof \Closure) || (is_array($middleware) && class_exists($middleware[0]))) {
            $container[] = $middleware;

        } else throw new \Exception("Cannot register Middleware for session - invalid callback");
        return $this;
    }
}