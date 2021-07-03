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
     * 
     * SO:
     * 
     * Firstly, we need to build the tree of calls, i.e.:
     * When expression is "$upper($repeat(%TEST%,$add(2,3,4)))", then we build tree somehow like that:
     * 1. $add(2,3,4)              = 9
     * 2. %TEST%                   = let's say it returns x
     * 3. $repeat(x,9)             = repeats string 'x' 9 times, so returns xxxxxxxxx
     * 4. $upper(xxxxxxxxx)        = returns XXXXXXXXX
     * 
     * We know that we need to look for any expressions [] or functions $fun(), but the deepest one. Let's build an array of calls - pushing next one on the beginning
     * [1] = $upper
     * 
     * but on the next step:
     * [1] = $repeat
     * [2] = $upper
     * 
     * and next:
     * 
     * [1] = $add
     * [2] = $repeat
     * [3] = $upper
     * 
     * Why not the %TEST% tag? Because we will check its runtime status when we will make $repeat.
     * 
    */

    private $controlChars = [
        'EXPRESSION_OPENING' => '[',
        'EXPRESSION_CLOSING' => ']',
        'EXPRESSION_NO_CHECK' => '\'',
        'TAG' => '%',
        'FUNCTION_OPENING' => '$',
        'FUNCTION_ARGUMENTS_OPENING' => '(',
        'FUNCTION_ARGUMENTS_SEPARATOR' => ',',
        'FUNCTION_ARGUMENTS_CLOSING' => ')'
    ];

    private $functions = [
        'upper' => 'strotoupper',
        'lower' => 'strotolower'
    ];

    private $tags = [
        "LANG" => "pl"
    ];

    private $variables = [];

    private $currentPos = 0;
    private $expression = '';
    private $lastTagEmpty = true;

    public function registerFunction($name, $callback)
    {
        $this->functions[$name] = $callback;
        return $this;
    }

    public function unregisterFunction($name)
    {
        unset($this->functions[$name]);
    }

    public function registerTag($name, $value)
    {
        $this->tags[$name] = $value;
        return $this;
    }
    
    public function unregisterTag($name)
    {
        unset($this->tags[$name]);
        return $this;
    }

    private function registerVariable($name, $value)
    {
        $this->variables[$name] = $value;
    }
    
    public function unregisterVariable($name)
    {
        unset($this->variables[$name]);
        return $this;
    }

    public function parse($expression = '')
    {
        /**
         * definition	=
         * concatenation	,
         * termination	;
         * alternation	|
         * optional	[ ... ]
         * repetition	{ ... }
         * grouping	( ... )
         * terminal string	" ... "
         * terminal string	' ... '
         * comment	(* ... *)
         * special sequence	? ... ?
         * exception	-
         * 
         * lower letter = "a" | "b" | "c" | "d" | "e" | "f" | "g" | "h" | "i" | "j" | "k" | "l" | "m" | "n" | "o" | "p" | "q" | "r" | "s" | "t" | "u" | "v" | "w" | "x" | "y" | "z"
         * upper letter = "A" | "B" | "C" | "D" | "E" | "F" | "G" | "H" | "I" | "J" | "K" | "L" | "M" | "N" | "O" | "P" | "Q" | "R" | "S" | "T" | "U" | "V" | "W" | "X" | "Y" | "Z"
         * digit = "0" | "1" | "2" | "3" | "4" | "5" | "6" | "7" | "8" | "9"
         * any letter = lower letter | upper letter
         * 
         * function identifier = any letter | "_" , { any letter | digit | "_" }
         * tag identifier = upper letter | "_" ,  upper letter | digit | "_" }
         * tag = "%" , tag identifier , "%"
         * 
         * expression = "[" , ( { any char - "[" - "]" } | tag | function | expression ) , "]"
         * function = "$" , function identifier , { "(" function argument , { "," , function argument } }
         * function argument = ( {all characters - '(' - ')' - ','} | "','" | "'('" | "')'" ) | tag | function | expression
         * 
         */
        return true;
        $output = '';
        $this->expression = $expression;
        $this->currentPos = 0;
        while (!$this->isPastEnd()) {
            $char = $this->getCurrentChar();
            $controlChar = $this->isControlChar($char);
            if (!$controlChar) $output .= $char;
            else {
                $nextChar = $this->getNthChar(1);
                if ($nextChar != '' && $controlChar === $this->isControlChar($nextChar)) {
                    $output .= $char;
                } else {
                    switch ($controlChar) {
                        case 'TAG':
                            $value = $this->parseTag();
                            $output .= $value;
                            break;
                        case 'FUNCTION_OPENING':
                            $value = $this->parseFunction();
                            var_dump($value);
                            break;
                        default:
                            break;
                    }
                }
            }
            $this->currentPos++;
        }
        return $output;
    }

    private function isPastEnd()
    {
        return $this->currentPos >= mb_strlen($this->expression);
    }

    private function getCurrentChar($next = false)
    {
        $char = $this->getNthChar();
        if ($next === true) $this->currentPos++;
        return $char;
    }

    private function getNthChar($n = 0)
    {
        return mb_substr($this->expression, $this->currentPos + $n, 1);
    }

    private function isControlChar($char)
    {
        return array_search($char, $this->controlChars);
    }

    private function parseTag()
    {
        $currentPos = $this->currentPos;
        $value = $this->tagExpression();
        if (!$value) {
            $this->currentPos = $currentPos;
            $value = $this->getCurrentChar();
        }
        return $value;
    }

    private function tagExpression()
    {
        if ($this->getCurrentChar(true) !== $this->controlChars['TAG']) return '';
        $name = $this->getCharsUntil($this->controlChars['TAG'], "/[A-Z_0-9]/");
        return $this->getTagValue($name);
    }

    private function getCharsUntil($stopChars, $allowedChars = true)
    {
        $name = '';
        while (!$this->isPastEnd()) {
            $char = $this->getCurrentChar(true);
            if (is_array($stopChars) && in_array($char, $stopChars)) break;
            else if (!is_array($stopChars) && strlen($stopChars) > 1 && substr($stopChars, 0, 1) === substr($stopChars, -1, 1) && preg_match($stopChars, $char) === 1) break;
            else if (is_string($stopChars) && $char === $stopChars) break;
            if ($allowedChars !== true) {
                if (is_array($allowedChars) && !in_array($char, $allowedChars)) return false;
                else if (!is_array($allowedChars) && strlen($allowedChars) > 1 && substr($allowedChars, 0, 1) === substr($allowedChars, -1, 1) && preg_match($allowedChars, $char) === 0) return false;
            }
            $name .= $char;
        }
        $this->currentPos--;
        return $name;
    }

    private function tagExists($name = '')
    {
        return isset($this->tags[$name]);
    }

    private function getTagValue($name = '')
    {
        $value = $this->tagExists($name) ? $this->tags[$name] : '';
        $this->lastTagEmpty = ($value == '');
        return $value;
    }

    private function parseFunction()
    {
        set_time_limit(3);
        $expression = $this->expression;
        $currentPos = $this->currentPos;
        $name = $this->getParsedFunctionName();
        if (!$name) {
            $this->currentPos = $currentPos;
            $name = $this->getCurrentChar();
            return $name;
        }
        $args = [];
        if ($this->getCurrentChar() === $this->controlChars['FUNCTION_ARGUMENTS_OPENING']) $args = $this->getParsedFunctionArgs();
        var_dump($args);
    }

    private function getParsedFunctionName()
    {
        if ($this->getCurrentChar(true) !== $this->controlChars['FUNCTION_OPENING']) return '';
        $name = $this->getCharsUntil("/" . preg_quote($this->controlChars['FUNCTION_OPENING']) . "|" . preg_quote($this->controlChars['FUNCTION_ARGUMENTS_OPENING']) . "|[^a-zA-Z_0-9]/", "/[a-zA-Z_0-9]/");
        return $name;
    }

    private function getParsedFunctionArgs()
    {
        $args = [];
        if ($this->getCurrentChar(true) !== $this->controlChars['FUNCTION_ARGUMENTS_OPENING']) return $args;
        while (!$this->isPastEnd() && $this->getCurrentChar() !== $this->controlChars['FUNCTION_ARGUMENTS_CLOSING']) {
            if ($this->getCurrentChar() === $this->controlChars['FUNCTION_ARGUMENTS_SEPARATOR']) $this->currentPos++;
            $args[] = $this->getParsedFunctionArg();
        }
        return $args;
    }

    private function getParsedFunctionArg()
    {
        $arg = '';
        while (!$this->isPastEnd()) {
            if ($this->getCurrentChar() === $this->controlChars['EXPRESSION_NO_CHECK']) $arg .= $this->getNonCheckedPart();
            if (in_array($this->getCurrentChar(), [$this->controlChars['FUNCTION_ARGUMENTS_CLOSING'], $this->controlChars['FUNCTION_ARGUMENTS_SEPARATOR']])) break;
            $arg .= $this->getCurrentChar(true);
        }
        return $arg;
    }

    private function getNonCheckedPart()
    {
        $this->currentPos++;
        $value = $this->getCharsUntil("/'/");
        if (mb_strlen($value) == 1) $this->currentPos++;
        return $value;
    }
}
