<?php

namespace app\core;

class View {

    const OVERWRITE_VARIABLES = 'overwrite';
    const NO_OVERWRITE_VARIABLES = 'no_overwrite';
    const ASSET_CSS = 'css';
    const ASSET_JS = 'js';
    const ASSET_MJS = 'mjs';
    const ASSET_INLINE = 'inline';
    const ASSET_MODULE = 'module';
    const ASSET_ASYNC = 'async';
    const ASSET_DEFER = 'defer';
    const ASSET_PRELOAD = 'preload';
    const ASSET_PREFETCH = 'prefetch';
    const ASSET_CROSSORIGIN = 'crossorigin';
    const ASSET_CUSTOM = 'custom';

    private $mode = self::OVERWRITE_VARIABLES;
    private $currentLayout = 'main';
    private $currentView = null;
    private $variables = [];
    private $assetsContainers = [];

    private $cacheFiles = true;
    private $cache = null;

    private $headerAssetsContainerPrinted = false;
    private $bodyBeginAssetsContainerPrinted = false;
    private $footerAssetsContainerPrinted = false;

    private $lateDynamicContentTemplate = ["<!-- {{ %s: ", " }} -->"];

    public function __construct()
    {
        $this->setCachingFromConfig();
        if ($this->isCacheEnabled()) $this->cache = new Cache();

        $this->createAssetsContainer('__HEADER__');
        $this->createAssetsContainer('__BODY_BEGIN__');
        $this->createAssetsContainer('__FOOTER__');

        return $this;
    }

    public function assign($key, $value = null)
    {
        if (!$this->canOverwrite() && isset($this->variables[$key])) {
            throw new \Exception("Variable {$key} already exists");
            return false;
        }
        $this->variables[$key] = $value;
        return $this;
    }

    public function prepend($key, $value = null)
    {
        if (!isset($this->variables[$key])) {
            $this->assign($key, $value);
        } else {
            $this->assign($key, $value . $this->variables[$key]);
        }
        return $this;
    }

    public function append($key, $value = null)
    {
        if (!isset($this->variables[$key])) {
            $this->assign($key, $value);
        } else {
            $this->assign($key, $this->variables[$key] . $value);
        }
        return $this;
    }

    public function canOverwrite()
    {
        return $this->mode === self::OVERWRITE_VARIABLES;
    }

    public function enableOverwrite()
    {
        $this->mode = self::OVERWRITE_VARIABLES;
    }

    public function disableOverwrite()
    {
        $this->mode = self::NO_OVERWRITE_VARIABLES;
    }

    public function setLayout($layout)
    {
        if (!$layout) return $this;
        if (!$this->viewElementExists($layout, 'VIEW_LAYOUTS')) {
            throw new \Exception("Layout {$layout} does not exists");
            return false;
        }
        $this->currentLayout = $layout;
        return $this;
    }

    public function setView($view)
    {
        if (!$view) return $this;
        if (!$this->viewElementExists($view, 'VIEWS')) {
            throw new \Exception("View {$view} does not exists");
            return false;
        }
        $this->currentView = $view;
        return $this;
    }

    private function viewElementExists($element = '', $path = 'VIEWS')
    {
        if (!isset(Application::$app->paths[$path])) {
            throw new \Exception("Path {$path} not exists in Views directory");
            return false;
        }
        return file_exists($this->getViewElementAsFilePath($element, $path));
    }

    private function getViewElementAsFilePath($element= '', $path = 'VIEWS')
    {
        $elementDir = Application::$app->paths[$path];
        $elementPath = pathinfo("{$elementDir}/{$element}");
        $elementPath = "{$elementPath['dirname']}/{$elementPath['filename']}.php";
        return $elementPath;
    }

    public function renderPart($file, $path = 'VIEWS')
    {
        if (!$this->viewElementExists($file, $path)) return false;
        $this->viewElementAsFilePath = $this->getViewElementAsFilePath($file, $path);
        $render = function() {
            extract($this->variables);
            @include $this->viewElementAsFilePath;
            unset($this->viewElementAsFilePath);
            // $definedVars = get_defined_vars();
            // foreach ($definedVars as $name => $value) $this->variables[$name] = $value;
        };
        return $render();
    }

    public function renderPartToString($file, $path = 'VIEWS')
    {
        ob_start();
        $this->renderPart($file, $path);
        return ob_get_clean();
    }

    public function part($part = null)
    {
        $this->renderPart($part, 'VIEW_PARTS');
        return $this;
    }

    public function view($view = null)
    {
        if (!$view || !$this->viewElementExists($view)) $view = $this->currentView;
        $this->renderPart($view, 'VIEWS');
        return $this;
    }

    public function render($view = null, $variables = null, $layout = null)
    {
        $this->setLayout($layout);
        $this->assignVariables($variables);
        $this->setView($view);
        $output = $this->renderPartToString($this->currentLayout, 'VIEW_LAYOUTS');
        $output = $this->resolveDynamicContent($output);
        echo $output;
        return true;
        // $assets(header',',je,%LANG%,$artist(a))

        // $parser = new TemplateParser();
        // echo $parser->parse($this->renderPartToString($this->currentLayout, 'VIEW_LAYOUTS'));
        // return true;
    }

    private function assignVariables($variables)
    {
        if (!is_array($variables)) return false;
        foreach ($variables as $key => $value) {
            if (is_string($key)) $this->assign($key, $value);
        }
        return $this;
    }

    private function resolveDynamicContent($output = '')
    {
        $app = Application::$app;

        $dynamicContents = [
            "Assets" => function($match) use ($app) {
                $container = $match[1];
                return $this->getAssetsContainer($container) . PHP_EOL;
            },
            "Variable" => function($match) use ($app) {
                $variable = $match[1];
                return $this->variables[$variable];
            }
        ];

        foreach ($dynamicContents as $contentType => $callback) {
            $output = preg_replace_callback($this->getDynamicContentPreg($contentType), $callback, $output);
        }

        return $output;
    }

    private function getDynamicContentPreg($type)
    {
        if ($type != preg_quote($type)) throw new \Exception("Type is invalid for Regular Expression");
        return "/" . preg_quote(sprintf($this->lateDynamicContentTemplate[0], $type), "/") . "(.+?)" . preg_quote($this->lateDynamicContentTemplate[1], "/") . "/u";
    }

    private function getDynamicContentTag($type, $name)
    {
        return sprintf($this->lateDynamicContentTemplate[0], $type) . $name . $this->lateDynamicContentTemplate[1];
    }

    public function var($name)
    {
        return $this->getDynamicContentTag("Variable", $name);
    }

    private function variableExists($name)
    {
        return isset($this->variables[$name]);
    }

    public function assets($containerName)
    {
        if ($this->assetsContainerExists($containerName)) {
            echo $this->getDynamicContentTag("Assets", $containerName);
        }
        return $this;
    }

    public function headerAssets()
    {
        if (!$this->headerAssetsContainerPrinted) $this->headerAssetsContainerPrinted = true;
        $this->assets('__HEADER__');
        return $this;
    }

    public function bodyBeginAssets()
    {
        if (!$this->bodyBeginAssetsContainerPrinted) $this->bodyBeginAssetsContainerPrinted = true;
        $this->assets('__BODY_BEGIN__');
        return $this;
    }

    public function footerAssets()
    {
        if (!$this->footerAssetsContainerPrinted) $this->footerAssetsContainerPrinted = true;
        $this->assets('__FOOTER__');
        return $this;
    }

    public function createAssetsContainer($name = '')
    {
        if ($this->assetsContainerExists($name)) return $this->assetsContainers[$name];
        $this->assetsContainers[$name] = [];
        return $this;
    }

    public function getAssetsContainer($container = '')
    {
        if (!$this->assetsContainerExists($container)) return null;
        if (empty($this->assetsContainers[$container])) return '';
        $containerID = json_encode($this->assetsContainers[$container]);
        if ($this->isCacheEnabled() && $this->cache->exists($containerID)) {
            $output = $this->cache->getCached($containerID);
            Application::$app->logger->log("Container \"{$container}\" retrieved from \"" . $this->cache->getCacheFilePath($containerID) . "\"");
        } else {
            $output = '';
            foreach ($this->assetsContainers[$container] as $item) {
                if (!is_array($item)) $item = [$item];
                $path = array_shift($item);
                $custom = in_array(self::ASSET_CUSTOM, $item);
                if ($custom) {
                    $output .= $path;
                    continue;
                }
                $inline = in_array(self::ASSET_INLINE, $item);
                if ($inline) $output .= $this->getAssetTagInline($path, $item);
                else $output .= $this->getAssetTag($path, $item);
            }
            if ($this->isCacheEnabled()) {
                $this->cache->store($containerID, $output);
                Application::$app->logger->log("Container \"{$container}\" cached as " . $this->cache->getCacheFilePath($containerID));
            }
        }
        return $output;
    }

    private function assetsContainerExists($container = '')
    {
        return isset($this->assetsContainers[$container]);
    }

    private function getAssetTagInline($path, $options)
    {
        $output = '';
        $content = $this->getFile($path);
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (in_array(self::ASSET_CSS, $options) || $ext === 'css') $ext = 'css';
        else if (in_array(self::ASSET_JS, $options) || $ext === 'js') $ext = 'js';
        else if (in_array(self::ASSET_MJS, $options) || $ext === 'mjs') {
            $ext = 'mjs';
            $module = true;
        }
        if ($ext === 'css') {
            $output .= "<style type=\"text/css\">{$content}</style>";
        } else if (in_array($ext, ['js', 'mjs'])) {
            $module = in_array(self::ASSET_MODULE, $options);
            $output .= "<script";
            if ($module || $ext === 'mjs') $output .= " type=\"module\"";
            else $output .= " type=\"text/javascript\"";
            $output .= ">{$content}</script>";
        }
        return $output;
    }

    private function getAssetTag($path, $options)
    {
        $output = '';
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $preload = in_array(self::ASSET_PRELOAD, $options);
        $prefetch = in_array(self::ASSET_PREFETCH, $options);
        if (in_array(self::ASSET_CSS, $options) || $ext === 'css') $ext = 'css';
        else if (in_array(self::ASSET_JS, $options) || $ext === 'js') $ext = 'js';
        else if (in_array(self::ASSET_MJS, $options) || $ext === 'mjs') {
            $ext = 'mjs';
            $module = true;
        }
        if ($preload || $prefetch) {
            $preMode = $preload ? "preload" : "prefetch";
            $crossorigin = in_array(self::ASSET_CROSSORIGIN, $options);
            $preloadAdd = '';
            if ($ext === 'css') {
                $as = 'style';
                $preloadAdd = "<link rel=\"stylesheet\" media=\"print\" href=\"{$path}\" onload=\"this.media='all'\" />";
            } else if (in_array($ext, ['js', 'mjs'])) $as = 'script';
            else if (in_array($ext, ['htm', 'html', 'xml', 'php', 'py', 'c', 'cpp'])) $as = 'document';
            else if (in_array($ext, ['jpg', 'jpeg', 'png', 'apng', 'gif', 'webp', 'bmp', 'jfif', 'svg', 'ico'])) $as = 'image';
            else if (in_array($ext, ['wav', 'wave', 'mp3', 'm4a', 'ogg', 'ape', 'flac'])) $as = 'audio';
            else if (in_array($ext, ['mp4', 'flv', 'webm', '3gp', '3gpp', 'ogv', 'avi', 'mkv'])) $as = 'video';
            else if (in_array($ext, ['woff', 'woff2', 'ttf', 'otf'])) {
                $as = 'font';
                $crossorigin = true;
            }

            $output .= "<link rel=\"{$preMode}\" as=\"{$as}\" href=\"{$path}\"";
            if ($crossorigin) $output .= " crossorigin";
            $output .= "/>{$preloadAdd}";
        }
        if ($ext === 'css') {
        } else if (in_array($ext, ['js', 'mjs'])) {
            $module = in_array(self::ASSET_MODULE, $options);
            $async = in_array(self::ASSET_ASYNC, $options);
            $defer = in_array(self::ASSET_DEFER, $options);
            $output .= "<script";
            if ($module) $output .= " type=\"module\"";
            else $output .= " type=\"text/javascript\"";
            $output .= " src=\"{$path}\"";
            if ($async) $output .= " async";
            if ($defer) $output .= " defer";
            $output .= "></script>";
        }
        return $output;
    }

    public function prependHeaderAsset($name, $resource)
    {
        $resource = array_slice(func_get_args(), 1);
        return $this->prependResourceToContainer('__HEADER__', $name, $resource);
    }

    public function prependBodyBeginAsset($name, $resource)
    {
        $resource = array_slice(func_get_args(), 1);
        return $this->prependResourceToContainer('__BODY_BEGIN__', $name, $resource);
    }

    public function prependFooterAsset($name, $resource)
    {
        $resource = array_slice(func_get_args(), 1);
        return $this->prependResourceToContainer('__FOOTER__', $name, $resource);
    }

    public function addHeaderAsset($name, $resource)
    {
        $resource = array_slice(func_get_args(), 1);
        return $this->appendResourceToContainer('__HEADER__', $name, $resource);
    }

    public function addBodyBeginAsset($name, $resource)
    {
        $resource = array_slice(func_get_args(), 1);
        return $this->appendResourceToContainer('__BODY_BEGIN__', $name, $resource);
    }

    public function addFooterAsset($name, $resource)
    {
        $resource = array_slice(func_get_args(), 1);
        return $this->appendResourceToContainer('__FOOTER__', $name, $resource);
    }

    private function prependResourceToContainer($container = '', $name, $resource)
    {
        if (!$this->assetsContainerExists($container)) return null;
        $resource = array_slice(func_get_args(), 2);
        $resource = Helpers::flattenArray($resource);
        if (!$name || $name === '') {
            array_unshift($this->assetsContainers[$container], $resource);
        } else {
            $this->assetsContainers[$container] = array_merge_recursive([$name => $resource], $this->assetsContainers[$container]);
        }
        return $this;
    }

    private function prependResourceBeforeResourceInContainer($container = '', $beforeName, $name, $resource)
    {
        if (!$this->assetsContainerExists($container)) return null;
        $resource = array_slice(func_get_args(), 3);
        $resource = Helpers::flattenArray($resource);
        $keyPosition = Helpers::arrayKeyPos($this->assetsContainers[$container], $beforeName);
        $this->assetsContainers[$container] = array_merge_recursive(
            array_slice($this->assetsContainers[$container], 0, $keyPosition, true),
            [$name => $resource],
            array_slice($this->assetsContainers[$container], $keyPosition, null, true)
        );
        return $this;
    }

    private function appendResourceToContainer($container = '', $name, $resource)
    {
        if (!$this->assetsContainerExists($container)) return null;
        $resource = array_slice(func_get_args(), 2);
        $resource = Helpers::flattenArray($resource);
        if (!$name || $name === '') {
            array_push($this->assetsContainers[$container], $resource);
        } else {
            $this->assetsContainers[$container] = array_replace_recursive($this->assetsContainers[$container], [$name => $resource]);
        }
        return $this;
    }

    private function appendResourceAfterResourceInContainer($container = '', $afterName, $name, $resource)
    {
        if (!$this->assetsContainerExists($container)) return null;
        $resource = array_slice(func_get_args(), 3);
        $resource = Helpers::flattenArray($resource);
        $keyPosition = Helpers::arrayKeyPos($this->assetsContainers[$container], $afterName) + 1;
        $this->assetsContainers[$container] = array_replace_recursive(
            array_slice($this->assetsContainers[$container], 0, $keyPosition, true),
            [$name => $resource],
            array_slice($this->assetsContainers[$container], $keyPosition, null, true)
        );
        return $this;
    }

    public function disableCache()
    {
        $this->cacheFiles = false;
        return $this;
    }

    public function enableCache()
    {
        $this->cacheFiles = true;
        return $this;
    }

    public function setCachingFromConfig()
    {
        if (isset($_ENV['CACHE_ENABLED']) && $_ENV['CACHE_ENABLED'] === 'false') $this->disableCache();
        else $this->enableCache();
    }

    private function isCacheEnabled()
    {
        return $this->cacheFiles;
    }

    private function getFile($path) {
        if ($this->isCacheEnabled()) {
            $content = $this->cache->get($path);
        } else {
            $content = Helpers::getFile($path);
        }
        return $content;
    }
}