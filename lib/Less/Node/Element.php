<?php

namespace Less\Node;

class Element
{
    public $combinator;
    public $value;
    public function __construct($combinator, $value = '')
    {
        if ( ! ($combinator instanceof \Less\Node\Combinator)) {
            $combinator = new Combinator($combinator);
        }
        $this->value = trim($value);
        $this->combinator = $combinator;
    }

    public function toCSS ($env)
    {
        return $this->combinator->toCSS($env) . $this->value;
    }
}
