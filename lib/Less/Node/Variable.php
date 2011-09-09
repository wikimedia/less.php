<?php

namespace Less\Node;

class Variable
{
    public function __construct($name, $index)
    {
        $this->name = $name;
        $this->index = $index;
    }

    public function compile($env)
    {
        $name = $this->name;
        if (strpos($name, '@@') === 0) {
            $v = new \Less\Node\Variable(substr($name, 1), $this->index + 1);
            $name = '@' . $v->compile($env)->value;
        }
        $callback = function ($frame) use ($env, $name) {
            if ($v = $frame->variable($name)) {
                return $v->value->compile($env);
            }
        };
        if ($variable = \Less\Environment::find($env->frames, $callback)) {
            return $variable;
        } else {
            throw new \Less\Exception\CompilerException("variable " . $name . " is undefined", $this->index);
        }
    }
}
