<?php

namespace app\controllers;

use app\core\Application;
use app\core\Controller;
use app\core\database\Database;
use app\core\Helpers;
use app\core\middlewares\SessionMiddleware;
use app\core\Tree;
use app\core\TreeNode;

class SiteController extends Controller {
    public function home($request, $response)
    {
        $app = Application::$app;
        $app->view->assign("lang", "pl");
        $app->view->setView('home');

        $dbh = $app->dbh;

        $test = new class extends \app\core\database\DbModel {
            public function tableName()
            {
                return 'items';
            }
        };
        $test->x = null;
        $test->load(['x' => 5, 'value' => 3]);
        $test->insert();
        var_dump($test);

        $menusColumns = implode(", ", array_map(function($item) {
            return "menus.`{$item}`";
        }, ["id", "name", "display_name", "meta_title", "meta_desc", "meta_keys", "flink"]));
        $menusParentsColumns = implode(", ", array_map(function($item) {
            return "menus_parents.`{$item}`";
        }, ["parent_id", "order"]));

        $stmt = $dbh->prepare("SELECT {$menusColumns}, {$menusParentsColumns} FROM menus LEFT JOIN menus_parents ON menus.`id` = menus_parents.`menu_id` WHERE menus_parents.`language_id` = :lang AND menus_parents.`enabled` = 1 AND menus_parents.`parent_id` IS NULL");
        $stmt->bindValue('lang', $app->language);
        $stmt->execute();
        $result = $stmt->fetchAll(\PDO::FETCH_CLASS);
        var_dump($result);

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