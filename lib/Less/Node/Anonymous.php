<?php

namespace Less\Node;

class Anonymous
{
    public $value;

    public function __construct($value)
    {
        $this->value = is_string($value) ? $value : $value->value;
    }

    public function toCss()
    {
        return $this->value;
    }

    public function compile($env)
    {
        return $this;
    }
}
