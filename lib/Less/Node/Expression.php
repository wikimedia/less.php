<?php

namespace Less\Node;

class Expression
{
    public function __construct($value)
    {
        $this->value = $value;
    }
    public function compile($env) {
        if (is_array($this->value) && count($this->value) > 1) {
            return new \Less\Node\Expression(array_map(function ($e) use ($env) {
                return $e->compile($env);
            }, $this->value));
        } else if (is_array($this->value) && count($this->value) == 1) {
            return $this->value[0]->compile($env);
        } else {
            return $this;
        }
    }

    public function toCSS ($env) {
        return implode(' ', array_map(function ($e) use ($env) {
            return $e->toCSS($env);
        }, $this->value));
    }

}
