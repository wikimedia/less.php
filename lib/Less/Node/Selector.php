<?php

namespace Less\Node;

class Selector
{
    public $elements;
    private $_css;
    public function __construct($elements)
    {
        $this->elements = $elements;

        if (is_array($this->elements) && isset($this->elements[0]) &&
            $this->elements[0] instanceof \Less\Node\Combinator &&
            $this->elements[0]->combinator->value === '') {

            $this->elements[0]->combinator->value = ' ';
        }
    }

    public function match ($other)
    {
        $value = $this->elements[0]->value;
        $len   = count($this->elements);
        $olen  = count($other->elements);
        if ($len > $olen) {
            return $value === $other->elements[0]->value;
        }

        for ($i = 0; $i < $olen; $i ++) {
            if (is_array($other->elements) && $value === $other->elements[$i]->value) {
                for ($j = 1; $j < $len; $j ++) {
                    if ($this->elements[$j]->value !== $other->elements[$i + $j]->value) {
                        return false;
                    }
                }
                return true;
            }
        }
        return false;
    }

    public function toCSS ($env)
    {
        if ($this->_css) {
            return $this->_css;
        }

        $this->_css = array_map(function ($e) use ($env) {
            if (is_string($e)) {
                return ' ' . trim($e);
            } else {
                return $e->toCSS($env);
            }
        }, $this->elements);
        $this->_css = implode('', $this->_css);

        return $this->_css;
    }
}
