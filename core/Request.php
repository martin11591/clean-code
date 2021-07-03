<?php

namespace app\core;

class Request {
    public function getMethod()
    {
        return strtolower($_SERVER['REQUEST_METHOD']);
    }

    public function isGet()
    {
        return $this->getMethod() === 'get';
    }

    public function isPost()
    {
        return $this->getMethod() === 'post';
    }

    public function isPostPut()
    {
        return ($this->isPost() && $this->getPostMethod() === 'put');
    }

    public function isPostDelete()
    {
        return ($this->isPost() && $this->getPostMethod() === 'delete');
    }

    public function isPostPatch()
    {
        return ($this->isPost() && $this->getPostMethod() === 'patch');
    }

    public function isPostHead()
    {
        return ($this->isPost() && $this->getPostMethod() === 'head');
    }

    public function isPostOptions()
    {
        return ($this->isPost() && $this->getPostMethod() === 'options');
    }

    public function isPut()
    {
        return $this->getMethod() === 'put';
    }

    public function isDelete()
    {
        return $this->getMethod() === 'delete';
    }

    public function isPatch()
    {
        return $this->getMethod() === 'patch';
    }

    public function isHead()
    {
        return $this->getMethod() === 'head';
    }

    public function isOptions()
    {
        return $this->getMethod() === 'options';
    }

    public function getPostMethod()
    {
        $body = $this->getBody();
        return isset($body['X-REQUEST-METHOD']) ? strtolower($body['X-REQUEST-METHOD']) : (isset($body['HTTP_X_REQUEST_METHOD']) ? strtolower($body['HTTP_X_REQUEST_METHOD']) : 'post');
    }

    public function getBody()
    {
        $body = false;

        if ($this->isGet()) return $this->getMethodBody();
        if ($this->isPost()) {
            $body = $this->postMethodBody();
            return $body;
        }
        if (
            $this->isPut()
            || $this->isDelete()
            || $this->isPatch()
            || $this->isHead()
            || $this->isOptions()
        ) $body = $this->inputStreamBody();

        if ($body) {
            if ($this->isXML()) {
                $xml = simplexml_load_string($body, "SimpleXMLElement", LIBXML_NOCDATA);
                $json = json_encode($xml);
                $body = json_decode($json, TRUE);
            }
            if ($this->isJSON()) {
                $body = json_decode($body, true);
            }
            if ($this->isFormEncoded()) {
                parse_str($body, $body);
            }
            if ($this->isFormMultiPart()) {
                $contentType = $this->getContentType();
                $boundaryPos = strpos($contentType, "boundary=") + strlen("boundary=");
                $boundary = substr($contentType, $boundaryPos);
                $contents = explode($boundary, $body);
                foreach ($contents as $content) {
                    $lines = explode("\r\n", $content);
                    // ! TODO: fill FILES array with content disposition
                }
            }
        }

        return $body;
    }

    public function isText()
    {
        return $this->getContentType() === 'text/plain';
    }

    public function isFormEncoded()
    {
        return $this->getContentType() === 'application/x-www-form-urlencoded';
    }

    public function isFormMultiPart()
    {
        return substr($this->getContentType(), 0, 20) === 'multipart/form-data;';
    }

    public function isJSON()
    {
        return substr($this->getContentType(), 0, 16) === 'application/json';
    }

    public function isXML()
    {
        return substr($this->getContentType(), 0, 15) === 'application/xml';
    }

    public function getContentType()
    {
        return $this->getHeader("content-type");
    }

    public function getMethodBody()
    {
        $body = array_replace_recursive($_GET);
        array_walk_recursive($body, function($key, $item) {
            return filter_input(INPUT_GET, $key, FILTER_SANITIZE_SPECIAL_CHARS);
        });

        return $body;
    }

    public function postMethodBody()
    {
        $body = array_replace_recursive($_POST);
        array_walk_recursive($body, function($key, $item) {
            return filter_input(INPUT_POST, $key, FILTER_SANITIZE_SPECIAL_CHARS);
        });

        return $body;
    }

    public function inputStreamBody()
    {
        return file_get_contents('php://input');
    }

    public function getHeaders()
    {
        $headers = [];
        foreach (getallheaders() as $name => $value) $headers[$this->headerName($name)] = $value;
        return $headers;
    }

    private function headerName($name)
    {
        return strtolower($name);
    }

    public function getHeader($name)
    {
        $name = strtolower($name);
        $headers = $this->getHeaders();
        return isset($headers[$name]) ? $headers[$name] : false;
    }

    public function getCookies()
    {
        $cookies = array_replace_recursive($_COOKIE);
        array_walk_recursive($cookies, function($key, $item) {
            return filter_input(INPUT_COOKIE, $key, FILTER_SANITIZE_SPECIAL_CHARS);
        });

        return $cookies;
    }

    public function getFiles()
    {
        if ($this->isPost()) return $this->getPostFiles();
        if (
            $this->isPut()
            || $this->isDelete()
            || $this->isPatch()
            || $this->isHead()
            || $this->isOptions()
        ) return $this->getInputStreamFiles();

        return [];
    }

    public function getPostFiles()
    {
        return $_FILES;
    }

    public function getInputStreamFiles()
    {

    }

    public function getPath()
    {
        $base = rtrim($this->getBaseHref(), "\\/");
        $path = rtrim($this->getRelativePath(), "\\/");
        return $base . $path;
    }

    public function getBaseHref()
    {
        $env = Application::$app->environment;
        return "http"
        . ($env->https ? "s" : "")
        . "://{$env->domain}"
        . $env->paths['PUBLIC_ROOT_RELATIVE'];
    }

    public function getRelativePath()
    {
        $env = Application::$app->environment;
        // return $env->paths['REQUEST'];
        $document = rtrim($env->paths['SERVER_ROOT'], "\\/");
        $request = $this->getRequestUri();
        return Helpers::clearPath("/" . Helpers::removeSameString($document . $request, $env->paths['PUBLIC_ROOT'])[0]);
    }

    public function getRequestUri($removeQuery = true)
    {
        $uri = $_SERVER['REQUEST_URI'];
        if ($removeQuery === true && $_SERVER['QUERY_STRING'] != '') {
            $uri = substr($uri, 0, strrpos($uri, $_SERVER['QUERY_STRING']) - 1);
        }
        return Helpers::clearPath("/" . trim($uri, "\\/") . "/");
    }
}