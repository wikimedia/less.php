<?php

namespace Less\Node;

class Rule
{
    public function __construct($name, $value, $important = null, $index = null)
    {
        $this->name = $name;
        $this->value = ($value instanceof \Less\Node\Value) ? $value : new \Less\Node\Value(array($value));
        $this->important = $important ? ' ' . trim($important) : '';
        $this->index = $index;

        if ($name[0] === '@') {
            $this->variable = true;
        } else {
            $this->variable = false;
        }
    }


    public function toCSS ($env)
    {
        if ($this->variable) {
            return "";
        } else {

            return $this->name . ($env->compress ? ':' : ': ') . $this->value->toCSS($env) . $this->important . ";";
        }
    }

    public function compile ($context)
    {
        return new \Less\Node\Rule($this->name, $this->value->compile($context), $this->important, $this->index);
    }

}
