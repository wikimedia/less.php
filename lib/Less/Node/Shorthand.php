<?php

namespace Less\Node;

class Shorthand
{
    public function __construct($a, $b)
    {
        $this->a = $a;
        $this->b = $b;
    }

    public function toCss($env)
    {
        return $this->a->toCSS($env) . "/" . $this->b->toCSS($env);
    }

    public function compile($env)
    {
        return $this;
    }
}
