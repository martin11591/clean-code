<?php

namespace app\core;

class Environment {
    public $serverIP;
    public $remoteIP;
    public $domain;
    public $https = false;
    public $httpVer;
    public $isolated = false;
    public $paths;

    public function __construct()
    {
        $this->serverIP = $this->getServerIP();
        $this->remoteIP = $this->getRemoteIP();
        $this->domain = $this->getDomainName();
        $this->https = $this->isHttps();
        $this->httpVer = $this->getHttpVer();
        $this->paths = $this->findPaths();
        $this->isolated = $this->isIsolated();
        chdir("../");
        return $this;
    }

    private function getServerIP()
    {
        if (isset($_SERVER['SERVER_ADDR'])) return $_SERVER['SERVER_ADDR'];
        return null;
    }

    private function getRemoteIP()
    {
        if (isset($_SERVER['REMOTE_ADDR'])) return $_SERVER['REMOTE_ADDR'];
        return null;
    }

    private function getDomainName()
    {
        if (isset($_SERVER['HTTP_HOST'])) return $_SERVER['HTTP_HOST'];
        else if (isset($_SERVER['SERVER_NAME'])) return $_SERVER['SERVER_NAME'];
        else if ($this->isHttps() && isset($_SERVER['SSL_TLS_SNI'])) return $_SERVER['SSL_TLS_SNI'];
        return null;
    }

    private function findPaths()
    {
        $cwd = getcwd();
        $currentDir = __DIR__;
        $serverRoot = rtrim(Helpers::clearPath($_SERVER['DOCUMENT_ROOT']), "\\/");
        $siteRoot = rtrim(Helpers::clearPath($currentDir), "\\/");
        $siteRoot = substr($siteRoot, 0, strrpos($siteRoot, "/"));
        $serverRootPath = explode("/", $serverRoot);
        $siteRootPath = explode("/", $siteRoot);
        if (count($serverRootPath) > count($siteRootPath)) {
            $this->isolated = true;
            $siteRootRelative = ltrim(str_repeat("/..", count($serverRootPath) - count($siteRootPath)), "\\/");
        } else {
            $this->isolated = false;
            $siteRootRelative = Helpers::removeSameString($serverRoot, $siteRoot)[1];
        }
        $publicRoot = getcwd();
        if (!$publicRoot) $publicRoot = dirname($_SERVER['SCRIPT_FILENAME']);
        $publicRoot = rtrim(Helpers::clearPath($publicRoot), "\\/");
        if ($publicRoot != $serverRoot) $publicRootRelative = $siteRootRelative . Helpers::removeSameString($publicRoot, $siteRoot)[0];
        else $publicRootRelative = '';

        $path = [
            "SERVER_ROOT" => $serverRoot,
            "SITE_ROOT" => $siteRoot,
            "SITE_ROOT_RELATIVE" => $siteRootRelative,
            "PUBLIC_ROOT" => $publicRoot,
            "PUBLIC_ROOT_RELATIVE" => $publicRootRelative
        ];

        /*
        $trim = function($path = "", $add = "") {
            return trim($path, "\\/") . $add;
        };

        $rtrim = function($path = "", $add = "") {
            return rtrim($path, "\\/") . $add;
        };

        $massClean = function($paths = []) use ($trim, $rtrim) {
            foreach ($paths as &$arg) $arg = Helpers::clearPath($rtrim($arg, "/"));
            return $paths;
        };
        // Here started index.php - main entry point
        $cwd = getcwd();
        // Here is root of the project
        $script = dirname($_SERVER['SCRIPT_FILENAME']);
        $request = $_SERVER['REQUEST_URI'];
        $request = str_replace("?{$_SERVER['QUERY_STRING']}", "", $request);
        $document = $_SERVER['DOCUMENT_ROOT'];

        $path = $massClean([
            "CWD" => $cwd,
            "DOCUMENT" => $document,
            "PUBLIC_ROOT" => $script
        ]);

        $path["PUBLIC_ROOT_RELATIVE"] = "/" . str_replace($path["DOCUMENT"], "", $path["PUBLIC_ROOT"]);
        $path["SITE_ROOT"] = Helpers::sameString($path["PUBLIC_ROOT"], $path["DOCUMENT"] . ltrim($request, "\\/"));
        $path["SITE_ROOT_RELATIVE"] = "/" . str_replace($path["DOCUMENT"], "", $path["SITE_ROOT"]);

        $path = $massClean($path);
        $path["REQUEST"] = rawurldecode($request);
        $path["REQUEST"] = "/" . Helpers::removeSameString($path["REQUEST"], $path["SITE_ROOT_RELATIVE"])[0];
        */

        return $path;
    }

    private function isIsolated()
    {
        if (!isset($this->paths)) {
            $this->paths = $this->findPaths();
            return $this->paths['serverRoot'] === $this->paths['siteRoot'] && $this->paths['siteRoot'] === $this->path['publicRoot'];
        }
        $dir = false;
        if (isset($_SERVER['REDIRECT_TO_DIR'])) {
            $dir = str_replace(["\\\\", "\\", "//"], "/", $_SERVER['REDIRECT_TO_DIR']);
            $dir = strtolower($dir);
            $dir = trim($dir, "\\/");
        }
        $file = false;
        if (isset($_SERVER['REDIRECT_TO_FILE'])) {
            $file = str_replace(["\\\\", "\\", "//"], "/", $_SERVER['REDIRECT_TO_FILE']);
            $file = strtolower($file);
            $file = trim($file, "\\/");
        }

        return !($dir === 'public' && $file === 'index.php');
    }

    private function isHttps()
    {
        if (isset($_SERVER['REQUEST_SCHEME'])) {
            return strtolower($_SERVER['REQUEST_SCHEME']) === 'https';
        } else if (isset($_SERVER['SERVER_PORT'])) {
            return $_SERVER['SERVER_PORT'] === 443;
        } else if (isset($_SERVER['HTTPS'])) {
            return strtolower($_SERVER['HTTPS']) === 'on';
        } else {
            return false;
        }
    }

    private function getHttpVer()
    {
        if (isset($_SERVER['SERVER_PROTOCOL'])) {
            $version = $_SERVER['SERVER_PROTOCOL'];
            $version = substr($version, strpos($version, "/") + 1);
            // $version = floatval($version);
            return $version;
        } else {
            return null;
        }
    }


    public function checkWorkingDirectories()
    {
        $this->checkViewPath();
    }

    private function checkViewPath()
    {
        $path = $this->getViewPath();
        if (!self::dirExists($path)) self::createDir($path);
    }

    private function getViewPath()
    {
        $viewPath = isset($_ENV['PATH_VIEWS']) ? Helpers::clearPath($_ENV['PATH_VIEWS']) : 'views';
        if ($viewPath == "") $viewPath = 'views';
        $viewPath = trim($viewPath, "\\/");
        return $viewPath;
    }

    public static function dirExists($path = '')
    {
        return file_exists($path);
    }

    public static function createDir($path = '')
    {
        try {
            mkdir($path, 0755, true);
        } catch (\Exception $e) {
            throw new \Exception("Cannot create directory: \"{$path}\"");
        }
        return true;
    }
}