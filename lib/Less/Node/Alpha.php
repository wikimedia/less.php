<?php

namespace Less\Node;

class Alpha
{
    private $value;

    public function __construct($val)
    {
        $this->value = $val;
    }

    public function toCss($env)
    {
        return "alpha(opacity=" . (is_string($this->value) ? $this->value : $this->value->toCSS()) . ")";
    }

    public function compile($env)
    {
        if ( ! is_string($this->value)) {
            $this->value = $this->value->compile($env);
        }
        return $this;
    }
}