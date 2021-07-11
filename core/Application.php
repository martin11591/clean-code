<?php

namespace app\core;

use app\core\Environment;
use app\core\Config;
use app\core\database\Database;

class Application {
    public $logger;
    public $environment;
    public $paths;
    public $config;
    public $request;
    public $response;
    public $session;

    public static $app;

    public function __construct()
    {
        self::$app = $this;

        $this->logger = new MemoryLogger(MemoryLogger::FILE_APPEND);
        $this->logger->saveTimeStamp();

        $this->environment = new Environment();
        $this->config = new Config();
        // $this->environment->checkWorkingDirectories();   // THIS SHOULD BE INSTALLER THING
        $this->paths = $this->environment->paths;
        $this->addPathsFromConfigToEnv();

        $this->request = new Request();
        $this->language = 1;    // * TODO: Language from DB

        $this->session = new Session();
        $this->response = new Response();

        $this->db = new Database($_ENV['DB_DSN'], $_ENV['DB_USER'], $_ENV['DB_PASSWORD']);
        $this->dbh = $this->db->connect();

        $this->controller = null;
        $this->view = new View();
        $this->view->setLayout("main");
        $this->assignDefaultValuesToView();
        $this->router = new Router();

        return $this;
    }

    public function __destruct()
    {
        $this->logger->log("Finished after " . $this->logger->getDiffFromCurrentTimeStamp() . PHP_EOL . "---" . PHP_EOL);
        $this->logger->dumpToFile(dirname(__DIR__) . "/logs/log.txt");
    }

    private function addPathsFromConfigToEnv()
    {
        foreach ($_ENV as $key => $value) {
            if (substr($key, 0, 5) != "PATH_") continue;
            $key = substr($key, 5);
            if (!isset($this->paths[$key])) {
                if (substr($key, 0, 7) == 'PUBLIC_') $value = "{$this->paths['PUBLIC_ROOT_RELATIVE']}/{$value}";
                $value = Helpers::clearPath($value);
                $value = rtrim($value, "\\/");
                $this->paths[$key] = $value;
            }
        }
        return $this;
    }

    private function assignDefaultValuesToView()
    {
        $data = [
            "lang" => "pl",
            "charset" => "UTF-8",
            "title" => $_ENV['APP_NAME']
        ];
        foreach ($data as $key => $value) $this->view->assign($key, $value);
        return $this;
    }

    public function run()
    {
        return $this->router->run();
    }
}