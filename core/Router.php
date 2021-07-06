<?php

namespace app\core;

class Router {
    private $routes = [
        "get" => [],
        "post" => [],
        "put" => [],
        "patch" => [],
        "delete" => [],
        "head" => [],
        "options" => [],
        "error" => []
    ];

    public $info = [
        "code" => 200,
        "messages" => [],
        "data" => []
    ];

    private function register($method, $route, $action, $name = null, $cacheable = false)
    {
        $method = strtolower($method);
        if ($method != "all" && !$this->isMethodSupported($method)) {
            throw new \Exception("Method {$method} not supported");
        }
        $this->routes[$method][] = [
            'name' => $name,
            'route' => trim(Helpers::clearPath($route), "\\/"),
            'action' => $action,
            'cacheable' => $cacheable === true ? true : false
        ];

        return $this;
    }

    private function isMethodSupported($method)
    {
        $method = strtolower($method);
        return in_array($method, array_keys($this->routes));
    }

    public function get($route, $action, $name = null, $cacheable = false)
    {
        return $this->register("get", $route, $action, $name, $cacheable);
    }

    public function post($route, $action, $name = null, $cacheable = false)
    {
        return $this->register("post", $route, $action, $name, $cacheable);
    }

    public function put($route, $action, $name = null, $cacheable = false)
    {
        return $this->register("put", $route, $action, $name, $cacheable);
    }

    public function patch($route, $action, $name = null, $cacheable = false)
    {
        return $this->register("patch", $route, $action, $name, $cacheable);
    }

    public function delete($route, $action, $name = null, $cacheable = false)
    {
        return $this->register("delete", $route, $action, $name, $cacheable);
    }

    public function head($route, $action, $name = null, $cacheable = false)
    {
        return $this->register("head", $route, $action, $name, $cacheable);
    }

    public function options($route, $action, $name = null, $cacheable = false)
    {
        return $this->register("options", $route, $action, $name, $cacheable);
    }

    public function all($route, $action, $name = null, $cacheable = false)
    {
        return $this->register("all", $route, $action, $name, $cacheable);
    }

    public function error($code, $action, $name = null, $cacheable = false)
    {
        return $this->register("error", $code, $action, $name, $cacheable);
    }

    private function resolve()
    {
        $request = Application::$app->request;
        $paths = Application::$app->paths;
        $method = $request->getMethod();
        if ($method == 'post') $method = $request->getPostMethod();
        $uri = $request->getPath();
        $uriToTest = trim(Helpers::removeSameString($request->getRelativePath(), $paths['SITE_ROOT_RELATIVE'])[0], "\\/");

        $routes = &$this->routes[$method];
        $routeFound = false;

        if ($routes) foreach ($routes as $item) {
            $route = explode("/", $item['route']);
            $uri = explode("/", $uriToTest);
            if (count($uri) >= count($route) && $this->isUriMatchingRoute($uriToTest, $item['route'])) {
                $routeFound = $item;
                break;
            }
        }

        if ($routeFound) {
            if ($item['cacheable'] === true) {
                // ! TODO Later
                $this->dispatch($item, $uriToTest);
            } else $this->dispatch($item, $uriToTest);
        } else {
            $this->resolveError(404, $uriToTest);
            exit;
        }
    }

    private function resolveError($code, $uri = '', $message = false, $data = false)
    {
        http_response_code($code);
        $this->info['code'] = $code;
        if ($message) $this->info['messages'][] = $message;
        if ($data) $this->info['data'][] = $data;

        $routes = &$this->routes['error'];
        $routeFound = false;

        if ($routes) foreach ($routes as $item) {
            if ($item['route'] === (string) $code) {
                $routeFound = $item;
                break;
            }
        }

        if ($routeFound) {
            if ($item['cacheable'] === true) {
                // ! TODO Later
                $this->dispatch($item, $uri);
            } else $this->dispatch($item, $uri);
        } else {
            exit;
        }
    }

    private function isUriMatchingRoute($uri = '', $route = '')
    {
        $route = explode("/", $route);
        $uri = explode("/", $uri);
        $match = true;
        for ($routeIndex = 0, $uriIndex = 0; $routeIndex < count($route), $uriIndex < count($uri); ) {
            if (!isset($route[$routeIndex]) || !isset($uri[$uriIndex])) {
                $match = false;
                break;
            }

            $currentRoute = $route[$routeIndex];
            $currentUri = $uri[$uriIndex];

            if ($currentRoute == $currentUri) {
                $routeIndex++;
                $uriIndex++;
                continue;
            }

            if ($this->isUriMatchingFilledRoute($currentRoute, $currentUri)) {
                $routeIndex++;
                $uriIndex++;
                continue;
            }

            $match = false;

            if (!$match) break;
        }
        return $match;
    }

    private function isUriMatchingFilledRoute($route = '', $uri = '')
    {
        if (!$this->isRouteVariable($route)) return false;
        $preg = $this->routeVariableToPreg($route);
        return !!preg_match($preg, $uri) !== false;
    }

    private function isRouteVariable($route = '')
    {
        return mb_strpos($route, "{") !== false && mb_strpos($route, "}");
    }

    private function routeVariableToPreg($route = '', $single = true)
    {
        $variable = trim($route, "{}");
        $optionable = mb_substr($variable, -1, 1) == "?" ? true : false;
        $colonPos = mb_strrpos($variable, ":");
        if ($colonPos === false) $variable = [null, $variable];
        else $variable = [
            mb_substr($variable, 0, $colonPos),
            mb_substr($variable, $colonPos + 1)
        ];

        $regExToCheckType = "/^(b|bool|c(?:har(?:acter)?)?=.+|char(?:acter)?s=.+|d(?:igits)?|i(?:nt(?:eger)?)?|f(?:loat)?(=[0-9]+)?)$/";
        if ($variable[0]) $variable[0] = preg_replace("/[^0-9a-z_-]/", "_", $variable[0]);
        $variable[1] = preg_match($regExToCheckType, $variable[1]) !== false ? $variable[1] : "str";

        $preg = "";
        if (in_array($variable[1], ["b", "bool"])) $preg = "[01]";
        else if (in_array($variable[1], ["d", "digit"])) $preg = "[0-9]";
        else if (in_array($variable[1], ["i", "int", "integer", "n", "num", "number", "numbers", "digits"])) $preg = "[0-9]+";
        else if (in_array($variable[1], ["w", "word"])) $preg = "[a-z]+";
        else if (substr($variable[1], 0, strlen("character=")) == "character="
            || substr($variable[1], 0, strlen("char=")) == "char="
            || substr($variable[1], 0, strlen("c=")) == "c=") $preg = "[" . $this->escapeForRegExBrackets(substr($variable[1], strpos($variable[1], "=") + 1)) . "]";
        else if (substr($variable[1], 0, strlen("characters=")) == "characters="
            || substr($variable[1], 0, strlen("chars=")) == "chars=") $preg = "[" . $this->escapeForRegExBrackets(substr($variable[1], strpos($variable[1], "=") + 1)) . "]+";
        else if ($variable[1] == "slug") $preg = "[0-9a-z-]+";

        $preg = "/(" . ($variable[0] ? "?'{$variable[0]}'" : "") . "{$preg})" . ($optionable ? "?" : "") . ($single === true ? "\/?$/" : "");
        return $preg;
    }

    private function escapeForRegExBrackets($str = '')
    {
        if (substr($str, 0, 1) == "^") $str = "\\{$str}";
        return str_replace(["]", "/"], ["\\]", "\\/"], $str);
    }

    private function dispatch($entry, $uri = '')
    {
        $route = explode("/", $entry['route']);
        foreach ($route as &$item) {
            if ($this->isRouteVariable($item)) $item = trim($this->routeVariableToPreg($item, false), "/");
        }
        $route = "/" . implode("\/", $route) . "/";

        $matches = [];
        preg_match_all($route, $uri, $matches);
        $newMatches = [];
        foreach ($matches as $key => $value) {
            if ($key === 0) continue;
            if (gettype($key) == 'string' && isset($value[0])) $newMatches[$key] = $value[0];
            else if (isset($value[0])) $newMatches[] = $value[0];
        }
        $matches = $newMatches;

        $callback = $entry['action'];
        if (is_string($callback)) {
            return Application::$app->view->render();
        }
        if (is_callable($callback) && $callback instanceof \Closure) {
            $callback = $callback->bindTo(Application::$app);
        } else if (gettype($callback) == 'array') {
            // Instantiate class to get access to the methods
            Application::$app->controller = $callback[0] = new $callback[0]();
            if (!method_exists($callback[0], $callback[1])) throw new \Exception("Controller \"" . get_class($callback[0]) . "\" does not have \"{$callback[1]}\" method");
            $middlewares = $callback[0]->getMiddlewares();
            foreach ($middlewares as $middleware) $middleware->execute();
        }
        return call_user_func($callback, Application::$app->request, Application::$app->response, $matches);
    }

    public function callError($code, $message = false, $data = false)
    {
        while (ob_get_level() > 1) ob_end_clean();
        $this->resolveError($code, Application::$app->request->getRequestUri(), $message, $data);
        if ($message) Application::$app->view->assign('message', $message);
        exit;
    }

    public function run()
    {
        try {
            return $this->resolve();
        } catch (\Exception $e) {
            var_dump($e);
            return false;
        }
        return true;
    }
}