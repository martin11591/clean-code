<?php

namespace app\core;

use app\core\Application;

class Cache {
    public function __construct()
    {
        if (!$this->checkFolder()) $this->createFolder();
        return $this;
    }

    private function getCachePathRelative()
    {
        return rtrim(Helpers::clearPath(isset($_ENV['PATH_CACHE']) ? $_ENV['PATH_CACHE'] : 'cache'), "\\/");
    }

    private function getCachePath()
    {
        return Application::$app->paths['SITE_ROOT'] . '/' . $this->getCachePathRelative();
    }

    private function checkFolder()
    {
        return file_exists($this->getCachePath());
    }

    private function createFolder()
    {
        return mkdir($this->getCachePath(), 0777);
    }

    public function get($file = '')
    {
        if ($file === '') return null;
        if (!Helpers::isLinkExternal($file)) {
            $content = Helpers::getFile($file);
        } else {
            $cacheFile = $this->getCacheFilePath($file);
            if ($this->exists($file) && !$this->isExpired($file)) {
                $content = Helpers::getFile($cacheFile);
                Application::$app->logger->log("Retrieved external resource \"{$file}\" from cache: \"{$cacheFile}\"");
            } else {
                $content = Helpers::getFile($file);
                if ($content != null) $this->store($file, $content);
            }
        }

        return $content;
    }

    public function getCached($id)
    {
        if ($id === '') return null;
        $cacheFile = $this->getCacheFilePath($id);
        if (!file_exists($cacheFile)) return null;
        $content = Helpers::getFile($cacheFile);
        Application::$app->logger->log("Retrieved external resource \"{$id}\" from cache: \"{$cacheFile}\"");
        return $content;
    }

    public function exists($file)
    {
        return file_exists($this->getCacheFilePath($file));
    }

    public function getCacheFilePath($file)
    {
        $file = $this->generateID($file);
        return $this->getCachePath() . "/{$file}";
    }

    private function isExpired($file)
    {
        $file = $this->getCacheFilePath($file);
        clearstatcache(false, $file);
        $fileModifiedDate = filemtime($file);
        $expirationDate = strtotime($this->getExpirationDate(), $fileModifiedDate);
        $currentDate = time();
        return $currentDate > $expirationDate;
    }

    public function getExpirationDate()
    {
        return isset($_ENV['CACHE_FILES_DURATION']) ? $_ENV['CACHE_FILES_DURATION'] : '1 week';
    }

    public function store($file, $content)
    {
        try {
            $cacheFile = $this->getCacheFilePath($file);
            $handler = fopen($cacheFile, 'w');
            fwrite($handler, $content);
            fclose($handler);
            Application::$app->logger->log("Cached external resource \"{$file}\" as \"{$cacheFile}\"");
            return $this;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function generateID($id)
    {
        return Helpers::hash($id);
    }
}