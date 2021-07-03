<?php

namespace app\core;

class Tree {
    protected $root = null;

    public function __construct(TreeNode $node = null)
    {
        if ($node === null) $node = new TreeNode("ROOT");
        $this->root = $node;
        return $this;
    }

    private function isRootNull()
    {
        return $this->root === null;
    }

    public function getRoot()
    {
        return $this->root;
    }

    public function root(TreeNode $node = null)
    {
        if ($node === null) return $this->root;
        $this->root = $node;
        return $this;
    }

    public function parent(TreeNode $node, TreeNode $parent)
    {
        return $node->parent($parent);
    }
}