<?php

namespace app\controllers;

use app\core\Application;
use app\core\Controller;

class ErrorController extends Controller {
    public function __construct()
    {
        $app = Application::$app;
        $app->view->setLayout('error');
        $app->view->setView('error');
        $app->view->assign('code', http_response_code());
        $app->view->assign('messages', $app->router->info['messages']);
        $app->view->assign('data', $app->router->info['data']);
    }

    public function notFound($request, $response)
    {
        return Application::$app->view->render();
    }

    public function internal($request, $response)
    {
        return Application::$app->view->render();
    }
}