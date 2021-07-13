<?php

namespace app\models;

use app\core\Application;
use app\core\database\DbModel;

class MenuModel extends DbModel {
    private static $entries = [];
    private static $root;
    private static $children = [];
    private static $trees = [];

    public $id = null;
    public $language_id = 1;
    public $name = '';
    public $display_name = '';
    public $meta_title = '';
    public $meta_desc = '';
    public $meta_keys = '';
    public $flink = '';
    public $enabled = 0;

    public function tableName()
    {
        return 'menus';
    }

    public function primaryKey()
    {
        return 'id';
    }

    public function fields()
    {
        return ['id', 'language_id', 'name', 'display_name', 'meta_title', 'meta_desc', 'meta_keys', 'flink', 'enabled'];
    }

    public function getRoot($enabledOnly = false)
    {
        if (self::$root) return self::$root;
        $dbh = Application::$app->dbh;
        $table = $this->tableName();
        $fields = $dbh->prepareColumns($this->fields());
        $tablePK = $this->primaryKey();
        $parentsTable = "{$table}_parents";
        $parentsFK = "menu_id";
        $enabledOnly = $enabledOnly === true ? " AND `{$table}`.`enabled` = 1" : "";
        $stmt = $dbh->prepare("SELECT {$fields}, `{$parentsTable}`.`parent_id` FROM `{$table}` LEFT JOIN `{$parentsTable}` ON `{$table}`.`{$tablePK}` = `{$parentsTable}`.`{$parentsFK}` WHERE `{$parentsTable}`.`parent_id` IS NULL{$enabledOnly} AND `{$table}`.`language_id` = " . Application::$app->language . " ORDER BY `{$parentsTable}`.`order_value` ASC, `{$table}`.`{$tablePK}` ASC");
        $stmt->execute();
        $result = $stmt->fetchAll(\PDO::FETCH_CLASS, get_class($this));
        self::$root = $result;
        return $result;
    }

    public function getChildren($parentID = 1, $enabledOnly = false)
    {
        if (isset(self::$children[$parentID])) return self::$children[$parentID];
        $dbh = Application::$app->dbh;
        $table = $this->tableName();
        $fields = $dbh->prepareColumns($this->fields());
        $tablePK = $this->primaryKey();
        $parentsTable = "{$table}_parents";
        $parentsFK = "menu_id";
        $enabledOnly = $enabledOnly === true ? " AND `{$table}`.`enabled` = 1" : "";
        $stmt = $dbh->prepare("SELECT {$fields}, `{$parentsTable}`.`parent_id` FROM `{$table}` LEFT JOIN `{$parentsTable}` ON `{$table}`.`{$tablePK}` = `{$parentsTable}`.`{$parentsFK}` WHERE `{$parentsTable}`.`parent_id` = {$parentID}{$enabledOnly} AND `{$table}`.`language_id` = " . Application::$app->language . " ORDER BY `{$parentsTable}`.`order_value` ASC, `{$table}`.`{$tablePK}` ASC");
        $stmt->execute();
        $result = $stmt->fetchAll(\PDO::FETCH_CLASS, get_class($this));
        self::$children[$parentID] = $result;
        return $result;
    }

    public function insert($doNotRemovePrimaryKey = false)
    {

    }

    public function update($fields = [], $primaryKey = null, $conditionals = "")
    {

    }

    public function delete($primaryKey = null)
    {
    }

    public function getEntry($id, $enabledOnly = false)
    {
        if (isset(self::$entries[$id])) return self::$entries[$id];
        $conditions = [$this->primaryKey() => $id];
        if ($enabledOnly === true) $conditions["enabled"] = 1;
        self::$entries[$id] = $this->findOneExact($conditions);
        return self::$entries[$id];
    }

    public function getTree($rootID = false, $enabledOnly = false)
    {
        if (!$rootID || intval($rootID) < 1) {
            if (isset(self::$trees['null'])) return self::$trees['null'];
        } else {
            if (isset(self::$trees[$rootID])) return self::$trees[$rootID];
        }
        $tree = [];
        if (!$rootID || intval($rootID) < 1) {
            $root = $this->getRoot($enabledOnly);
        } else {
            $root = $this->getEntry($rootID, $enabledOnly);
        }
        $queue = [$root];
        // $nestLevel = 0;
        while (!empty($queue)) {
            $entry = array_shift($queue);
            $tree[] = $entry;
            if (in_array($entry, ["-", "+"])) {
                // if ($entry == "-") {
                //     $nestLevel--;
                // }
                // if ($entry == "+") {
                //     $nestLevel++;
                // }
                continue;
            }
            $children = $this->getChildren($entry->id);
            if ($children) {
                array_unshift($queue, "-");
                for ($i = count($children) - 1; $i >= 0; $i--) {
                    array_unshift($queue, $children[$i]);
                }
                array_unshift($queue, "+");
            }
            // $entry->nestLevel = $nestLevel;
        }

        if (!$rootID || intval($rootID) < 1) {
            self::$trees['null'] = $tree;
        } else {
            self::$trees[$rootID] = $tree;
        }

        return $tree;
    }
}