<?php

namespace app\core;

class TemplateParser {
    /** 
     * Let's say, we have variables.
     * These variables should be replaced when outputting code to the browser.
     * We have at least two cases, what is gonna happen:
     * 1. Variables are changed before any output and process
     * 2. Variable can be changed in middle of the outputting
     * 3. Variables are changed after process but before output
     * Also, variables can recursively return each other, or can return string once, without further checks.
     * 
     * Expression consist of strings, variables and functions. If expression returns false, then there should not output anything.
     * 
     * Get function is counted towards result evalution.
     * That means, if we have [$get(a) - ] and a is set to x, then expression returns "x - ".
     * 
     * Put/set function is not counted towards result evaluation.
     * That means, if we have [$put(a,2) - ] then expression returns nothing.
     * 
     * At least one existing variable makes expression output result.
    */

    private $controlChars = [
        "EXPRESSION_OPENING" => "[",
        "EXPRESSION_CLOSING" => "]",
        "EXPRESSION_NO_CHECK" => "'",
        "TAG" => "%",
        "FUNCTION_OPENING" => "$",
        "FUNCTION_ARGUMENTS_OPENING" => "(",
        "FUNCTION_ARGUMENTS_SEPARATOR" => ",",
        "FUNCTION_ARGUMENTS_CLOSING" => ")"
    ];

    private $functions = [
        "upper" => "strotoupper",
        "lower" => "strotolower"
    ];

    private $currentPos;
    private $stack;
    private $buffer;
    private $output;
    private $expression;
    private $functionNestLevel = 0;
    private $expressionNestLevel = 0;

    public function __construct()
    {
        $this->stack = [
            'expressions' => [],
            'positions' => [],
            "outputs" => []
        ];
        $this->vars = [
            "test" => 5,
            "test2" => "te",
            "artist" => "X"
        ];
        $this->runtimeVars = [];
        $exp = 'xD%test% %%test%% $$upper $1test9 $upper($repeat(x,5)) $upper(%test%)[%artists% - $puts(a,%artists%)]%title%$get(a)';
        $this->parse($exp);
        var_dump($this->output);
    }

    public function parse($expression = '')
    {
        $this->output = '';
        $this->currentPos = 0;
        $this->expression = $expression;
        set_time_limit(3);

        while (true) {
            $controlChar = $this->getCurrentControlChar();
            if (!$controlChar) {
                $this->output .= $this->getChar();
            }
            $actionResult = $this->controlCharAction($controlChar);
            if ($actionResult === 'END_OF_EXPRESSION') break;
            $this->iteratePosition();
        }
    }

    private function getCurrentControlChar()
    {
        if ($this->currentPos >= strlen($this->expression)) return "END_OF_EXPRESSION";
        foreach ($this->controlChars as $controlName => $controlChar) {
            $cut = substr($this->expression, $this->currentPos, strlen($controlChar));
            if ($cut === $controlChar) return $controlName;
        }
        return false;
    }

    private function getChar()
    {
        return substr($this->expression, $this->currentPos, 1);
    }

    private function iteratePosition()
    {
        return $this->currentPos++;
    }

    private function decrementPosition()
    {
        return $this->currentPos--;
    }

    private function controlCharAction($controlChar)
    {
        switch ($controlChar) {
            case 'END_OF_EXPRESSION':
                return $controlChar;
                break;
            case 'EXPRESSION_OPENING':
                break;
            case 'EXPRESSION_CLOSING':
                break;
            case 'TAG':
                $this->output .= $this->parseTag();
                break;
            case 'FUNCTION_OPENING':
                $this->output .= $this->parseFunction();
                break;
            default:
                break;
        }
    }

    private function parseTag()
    {
        $this->iteratePosition();
        if ($this->getCurrentControlChar() === "TAG") return $this->getChar();
        $name = '';
        while (($controlChar = $this->getCurrentControlChar()) != "TAG") {
            $name .= $this->getChar();
            $this->iteratePosition();
        }
        if ($this->tagExists($name)) return $this->getTag($name);
        return '';
    }

    private function tagExists($name = '')
    {
        if (isset($this->vars[$name])) {
            return true;
        } else {
            return false;
        }
    }

    private function getTag($name = '')
    {
        return $this->vars[$name];
    }

    private function parseFunction()
    {
        $this->iteratePosition();
        if ($this->getCurrentControlChar() === "FUNCTION_OPENING") return $this->getChar();
        $name = $this->parseFunctionGetName();
        if (!$this->functionExists($name)) return '';
        $args = $this->parseFunctionGetArgs();
    }

    private function parseFunctionGetName()
    {
        $name = '';
        while (($controlChar = $this->getCurrentControlChar()) != "FUNCTION_ARGUMENTS_OPENING" && $controlChar != "END_OF_EXPRESSION" && !!preg_match('/[a-z0-9]/i', $this->getChar()) === true) {
            $name .= $this->getChar();
            $this->iteratePosition();
        }
        return $name;
    }
    
    private function functionExists($name = '')
    {
        if (isset($this->functions[$name])) {
            return true;
        } else {
            return false;
        }
    }

    private function parseFunctionGetArgs()
    {
        $this->iteratePosition();
        while (true) {
            $controlChar = $this->getCurrentControlChar();
            switch ($controlChar) {
                "FUNCTION_OPENING":
                    break;
                default:
                    break;
            }
            break;
        }
        exit;
        // $this->functionNestLevel++;
    }

    private function pushToStack()
    {
        $this->stack["expressions"][] = $this->expression;
        $this->stack["positions"][] = [$this->currentPos];
        $this->stack["outputs"][] = $this->output;
        return $this;
    }

    private function pullFromStack()
    {
        $this->expression = array_pop($this->stack["expressions"]);
        $this->currentPos = array_pop($this->stack["positions"]);
        $this->output = array_pop($this->stack["outputs"]);
        return $this;
    }
}