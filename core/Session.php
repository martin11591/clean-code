<?php

namespace app\core;

use app\core\Application;

class Session {
    const NON_EXISTENT_AS_NULL = 'nonexistent';
    const OVERWRITE = 'overwrite';
    const NO_OVERWRITE = 'no-overwrite';
    public static $nonExistentThrowsError = false;
    public static $overwrite = self::OVERWRITE;

    private $instance = null;
    private $modifiers = [
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
        $this->callModifiers($this->modifiers["before"]["get"], $pair);
        if ($pair->key === null) return $this->instance;
        if ($this->existsThrow($pair->key)) {
            $pair->value = $this->instance[$pair->key];
            $this->callModifiers($this->modifiers["after"]["get"], $pair);
            return $pair->value;
        } else return null;
    }
    
    public function set($key, $value = null)
    {
        if (self::$overwrite === self::OVERWRITE || (self::$overwrite === self::NO_OVERWRITE) && !$this->exists($key)) {
            $pair = $this->keyAndValueToObject($key, $value);
            $this->callModifiers($this->modifiers["before"]["set"], $pair);
            $this->instance[$pair->key] = $pair->value;
            $this->callModifiers($this->modifiers["after"]["set"], $pair);
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

    private function callModifiers($modifiers, $pair)
    {
        foreach ($modifiers as $callback) {
            // $callback = \Closure::bind($callback, Application::$app);
            $callback->call(Application::$app, $pair);
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

    public function registerBeforeGetModifier($callback)
    {
        return $this->registerModifier($this->modifiers["before"]["get"], $callback);        
    }

    public function registerAfterGetModifier($callback)
    {
        return $this->registerModifier($this->modifiers["after"]["get"], $callback);        
    }

    public function registerBeforeSetModifier($callback)
    {
        return $this->registerModifier($this->modifiers["before"]["set"], $callback);        
    }

    public function registerAfterSetModifier($callback)
    {
        return $this->registerModifier($this->modifiers["after"]["set"], $callback);        
    }

    private function registerModifier(&$container, $callback)
    {
        if ((is_callable($callback) && $callback instanceof \Closure) || (is_array($callback) && class_exists($callback[0]))) {
            $container[] = $callback;

        } else throw new \Exception("Cannot register get modifier for session - invalid callback");
        return $this;
    }
}