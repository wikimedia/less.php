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
        $len   = count($this->elements);
        $olen  = count($other->elements);
        $max = min($len, $olen);
        if ($len < $olen) {
            return false;
        } else {
            for ($i = 0; $i < $max; $i ++) {
                if ($this->elements[$i]->value !== $other->elements[$i]->value) {
                    return false;
                }
            }
        }
        return true;
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
