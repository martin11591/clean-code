<?php

namespace app\core;

class TreeNode {
    protected $parents = [];
    protected $children = [];
    public $data;

    public function __construct($data = null)
    {
        $this->data = $data;
        return $this;
    }

    public function data()
    {
        $args = func_get_args();
        if (!$args) return $this->data;
        if (count($args) === 1) $args = $args[0];
        $this->data = $args;
    }

    public function prependParent(TreeNode $node)
    {
        if (($index = $this->parentIndex($node)) !== false) {
            Helpers::removeElementFromArray($this->parents, $node);
        }
        array_unshift($this->parents, $node);
        if (!$node->childExists($this)) $node->prependChild($this);
        return $this;
    }

    public function prependParents()
    {
        $parents = func_get_args();
        foreach ($parents as $parent) {
            if ($parent instanceof TreeNode) $this->prependParent($parent);
        }
        return $this;
    }

    public function appendParent(TreeNode $node)
    {
        if (($index = $this->parentIndex($node)) !== false) {
            Helpers::removeElementFromArray($this->parents, $node);
        }
        array_push($this->parents, $node);
        if (!$node->childExists($this)) $node->appendChild($this);
        return $this;
    }

    public function appendParents()
    {
        $parents = func_get_args();
        foreach ($parents as $parent) {
            if ($parent instanceof TreeNode) $this->appendParent($parent);
        }
        return $this;
    }

    private function parentExists(TreeNode $node)
    {
        return in_array($node, $this->parents);
    }

    private function parentIndex(TreeNode $node)
    {
        return array_search($node, $this->parents);
    }

    public function prependChild(TreeNode $node)
    {
        if (($index = $this->childIndex($node)) !== false) {
            Helpers::removeElementFromArray($this->children, $node);
        }
        array_unshift($this->children, $node);
        if (!$node->parentExists($this)) $node->prependParent($this);
        return $this;
    }

    public function prependChildren()
    {
        $children = func_get_args();
        foreach ($children as $child) {
            if ($child instanceof TreeNode) $this->prependChild($child);
        }
        return $this;
    }

    public function appendChild(TreeNode $node)
    {
        if (($index = $this->childIndex($node)) !== false) {
            Helpers::removeElementFromArray($this->children, $node);
        }
        array_push($this->children, $node);
        if (!$node->parentExists($this)) $node->appendParent($this);
        return $this;
    }

    public function appendChildren()
    {
        $children = func_get_args();
        foreach ($children as $child) {
            if ($child instanceof TreeNode) $this->appendChild($child);
        }
        return $this;
    }

    private function childExists(TreeNode $node)
    {
        return in_array($node, $this->children);
    }

    private function childIndex(TreeNode $node)
    {
        return array_search($node, $this->children);
    }

    public function parents()
    {
        return $this->parents;
    }

    public function parent()
    {
        if (empty($this->parents)) return null;
        $preferredParents = func_get_args();
        $parent = $this->parents[0];
        foreach ($this->parents as $checkedParent) {
            if (in_array($checkedParent, $preferredParents)) {
                $parent = $checkedParent;
                break;
            }
        }
        return $parent;
    }

    public function children()
    {
        return $this->children;
    }

    public function child()
    {
        if (empty($this->children)) return null;
        $preferredChildren = func_get_args();
        $child = $this->children[0];
        foreach ($this->children as $checkedChild) {
            if (in_array($checkedChild, $preferredChildren)) {
                $child = $checkedChild;
                break;
            }
        }
        return $child;
    }
}