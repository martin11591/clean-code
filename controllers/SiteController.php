<?php

namespace app\controllers;

use app\core\Application;
use app\core\Controller;
use app\core\Database;
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
        /**
         * MENU:
         *  1 Produkty
         *  2     Buty
         *  3     Koszulki
         *  7     Test
         *  4 Kontakt
         *  5     O nas
         *  6     Informacje
         *  7     Test
         *
         * so we need three tables
         * table for item
         * table for parents
         * table for orders per parent
         *
         * ID   Parent ID
         *  1   null
         *  2   1
         *  3   1
         *  4   null
         *  5   4
         *  6   4
         *  7   1,4
         */
        $menu = new Tree();
        $produkty = new TreeNode([1, "produkty"]);
        $buty = new TreeNode([2, "buty"]);
        $koszulki = new TreeNode([3, "koszulki"]);
        $kontakt = new TreeNode([4, "kontakt"]);
        $onas = new TreeNode([5, "onas"]);
        $informacje = new TreeNode([6, "informacje"]);
        $test = new TreeNode([7, "test"]);
        $menu->getRoot()->appendChildren($produkty, $kontakt);
        $produkty->appendChildren($buty, $koszulki, $test);
        $kontakt->appendChildren($onas, $test, $informacje);

        /* var_dump($menu->getRoot()->child());

        $items = [
            1 => "produkty",
            2 => "buty",
            3 => "koszulki",
            4 => "kontakt",
            5 => "o nas",
            6 => "informacje",
            7 => "test"
        ];

        $parents = [
            1 => [null],
            2 => [1],
            3 => [1],
            4 => [null],
            5 => [4],
            6 => [4],
            7 => [1, 4]
        ];

        $orders = [
            1 => [10],
            2 => [10],
            3 => [20],
            4 => [20],
            5 => [10],
            6 => [20],
            7 => [30, 10]
        ];

        // GET TREE FROM ITEM ID 1
        $id = 1;
        foreach ($parents as $parent) {
            if (!in_array($id, $parent)) continue;

        } */

        // $a = [];
        // for ($i = 0; $i < 10000; $i++) array_unshift($a, 1);
        // $b = "";
        // for ($i = 0; $i < 10000; $i++) $b = "x|{$b}";
        // $start = microtime(true);
        // $step1 = microtime(true);
        // $end = microtime(true);
        // var_dump($step1 - $start, $end - $step1);

        $db = new Database('sqlite:test.db');
        $db = $db->connect();
        var_dump($db);
        $db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        var_dump($db->getAttribute(\PDO::ATTR_DRIVER_NAME));
        $db->query("CREATE TABLE IF NOT EXISTS items (id INTEGER PRIMARY KEY UNIQUE, value)");
        $db->query("CREATE TABLE IF NOT EXISTS parents (id INTEGER PRIMARY KEY UNIQUE, item_id INTEGER, parent_id INTEGER, order_value)");

        $stmt = $db->prepare("INSERT OR IGNORE INTO items (id, value) VALUES (1, ?), (2, ?), (3, ?), (4, ?), (5, ?), (6, ?), (7, ?)");
        $stmt->bindValue(1, 'produkty');
        $stmt->bindValue(2, 'buty');
        $stmt->bindValue(3, 'koszulki');
        $stmt->bindValue(4, 'kontakt');
        $stmt->bindValue(5, 'o nas');
        $stmt->bindValue(6, 'informacje');
        $stmt->bindValue(7, 'test');
        $stmt->execute();

        $stmt = $db->prepare("INSERT OR IGNORE INTO parents (id, item_id, parent_id, order_value) VALUES (1, ?, ?, ?), (2, ?, ?, ?), (3, ?, ?, ?), (4, ?, ?, ?), (5, ?, ?, ?), (6, ?, ?, ?), (7, ?, ?, ?), (8, ?, ?, ?)");
        $stmt->bindValue(1, 1);
        $stmt->bindValue(2, null);
        $stmt->bindValue(3, 10);
        $stmt->bindValue(4, 2);
        $stmt->bindValue(5, 1);
        $stmt->bindValue(6, 10);
        $stmt->bindValue(7, 3);
        $stmt->bindValue(8, 1);
        $stmt->bindValue(9, 20);
        $stmt->bindValue(10, 4);
        $stmt->bindValue(11, null);
        $stmt->bindValue(12, 20);
        $stmt->bindValue(13, 5);
        $stmt->bindValue(14, 4);
        $stmt->bindValue(15, 10);
        $stmt->bindValue(16, 6);
        $stmt->bindValue(17, 4);
        $stmt->bindValue(18, 20);
        $stmt->bindValue(19, 7);
        $stmt->bindValue(20, 1);
        $stmt->bindValue(21, 30);
        $stmt->bindValue(22, 7);
        $stmt->bindValue(23, 4);
        $stmt->bindValue(24, 15);
        $stmt->execute();

        $stmt = $db->prepare("SELECT items.id, items.value, parents.order_value FROM parents LEFT JOIN items ON items.id = parents.item_id WHERE parents.parent_id IS NULL ORDER BY parents.order_value ASC");
        $stmt->execute();
        $rootElements = [];
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $element) $rootElements[] = $element['id'];

        $stmt = $db->prepare("SELECT items.id, items.value, parents.order_value FROM parents LEFT JOIN items ON items.id = parents.item_id WHERE parents.parent_id = ? ORDER BY parents.order_value DESC");
        $level = 0;
        $test = [];
        var_dump($rootElements, $level);
        while (!empty($rootElements)) {
            var_dump($rootElements, $level);
            $id = array_shift($rootElements);
            $test[] = $id;
            if ($id === '-') {
                $level--;
                continue;
            }
            if ($id === '+') {
                $level++;
                continue;
            }
            $stmt->bindValue(1, $id);
            $stmt->execute();
            $ids = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            if (!empty($ids)) {
                array_unshift($rootElements, '-');
                foreach ($ids as $element) array_unshift($rootElements, $element['id']);
                array_unshift($rootElements, '+');
            }
            var_dump($rootElements, $level);
        }
        var_dump($rootElements, $level, $test);

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