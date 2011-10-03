<?php

namespace Less\Node;

class Directive
{
    public $name;
    public $value;
    public $ruleset;

    public function __construct($name, $value)
    {
        $this->name = $name;
        if (is_array($value)) {
            $this->ruleset = new Ruleset(false, $value);
            $this->ruleset->root = true;
        } else {
            $this->value = $value;
        }
    }

    public function toCSS($ctx, $env)
    {
        if ($this->ruleset) {
            $this->ruleset->root = true;
            return $this->name . ($env->compress ? '{' : " {\n  ") .
                   preg_replace('/\n/', "\n  ", trim($this->ruleset->toCSS($ctx, $env))) .
                   ($env->compress ? '}': "\n}\n");
        } else {
            return $this->name . ' ' . $this->value->toCSS() . ";\n";
        }
    }

    public function compile($env)
    {
        $env->unshiftFrame($this);
        $this->ruleset = $this->ruleset ? $this->ruleset->compile($env) : null;
        $env->shiftFrame();

        return $this;
    }
    // TODO: Not sure if this is right...
    public function variable($name)
    {
        return $this->ruleset->variable($name);
    }

    public function find($selector)
    {
        return $this->ruleset->find($selector, $this);
    }

    public function rulesets()
    {
        return $this->ruleset->rulesets();
    }

}
