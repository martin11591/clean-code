<?php

namespace app\core;

use app\core\exceptions\ConfigException;
use app\core\exceptions\ConfigFileNotExistsException;

interface ConfigInterface {
}

class Config implements ConfigInterface {
    private $dotenv;

    public function __construct($root = null)
    {
        if (!isset($root) || $root === null) $root = dirname(__DIR__);
        $this->openFileAndLoadToENV($root);
    }

    private function openFileAndLoadToENV($root)
    {
        $this->openFile($root);
        $this->load();
    }

    private function openFile($root)
    {
        if (!$this->fileExists($root)) throw new ConfigFileNotExistsException();
        $this->dotenv = \Dotenv\Dotenv::createImmutable($root);
    }

    private function fileExists($root)
    {
        return file_exists($root);
    }

    private function load()
    {
        $this->dotenv->load();
    }
}