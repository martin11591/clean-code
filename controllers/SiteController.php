<?php

namespace app\controllers;

use app\core\Application;
use app\core\Controller;
use app\core\Helpers;
use app\core\Tree;
use app\core\TreeNode;

class SiteController extends Controller {
    public function home($request, $response)
    {
        $app = Application::$app;
        $app->view->assign("lang", "pl");
        $app->view->setView('home');
        /**
         * MENU:
         *  Produkty
         *      Buty
         *      Koszulki
         *  Kontakt
         *      O nas
         *      Informacje
         */
        $menu = new Tree();
        $produkty = new TreeNode("produkty");
        $buty = new TreeNode("buty");
        $koszulki = new TreeNode("koszulki");
        $kontakt = new TreeNode("kontakt");
        $onas = new TreeNode("onas");
        $informacje = new TreeNode("informacje");
        $buty->appendParents($produkty, $kontakt);
        $buty->prependParent($kontakt);
        $menu->getRoot()->appendChildren($produkty, $kontakt);
        var_dump($menu->getRoot()->children());
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