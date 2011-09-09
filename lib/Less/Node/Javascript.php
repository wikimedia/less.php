<?php

namespace Less\Node;

class JavaScript
{
    public function __construct($string, $index, $escaped)
    {
        $this->escaped = $escaped;
        $this->expression = $string;
        $this->index = $index;
    }

    public function compile($env) {
        return '/* No javascript in PHP... :( */';
    }
}
