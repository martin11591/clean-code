<?php

namespace app\controllers;

use app\core\Application;
use app\core\Controller;
use app\models\MenuModel;

class SiteController extends Controller {
    public function home($request, $response)
    {
        $app = Application::$app;
        $app->view->assign("lang", "pl");
        $app->view->setView('home');

        $dbh = $app->dbh;

        $menus = new MenuModel;

        var_dump($menus->getTree(1));

        return $app->view->render();
    }

    public function contact($request, $response, $values)
    {
        $app = Application::$app;
        $app->view->assign("lang", "pl");
        $app->view->setView('contact');
        return $app->view->render();
    }

    public function contactPost($request, $response)
    {
        $app = Application::$app;
        var_dump($request->getBody());
    }
}