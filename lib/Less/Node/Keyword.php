<?php

namespace Less\Node;

class Keyword
{
    public function __construct($value)
    {
        $this->value = $value;
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
