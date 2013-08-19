<?php

namespace Less\Node;

class Variable {

	public $type = 'Variable';
	public $name;
	public $index;
	public $file;
	private $evaluating = false;

    public function __construct($name, $index, $file = null) {
        $this->name = $name;
        $this->index = $index;
		$this->file = $file;
    }

    public function compile($env) {
        $name = $this->name;
        if (strpos($name, '@@') === 0) {
            $v = new \Less\Node\Variable(substr($name, 1), $this->index + 1);
            $name = '@' . $v->compile($env)->value;
        }

		if ($this->evaluating) {
            throw new \Less\Exception\CompilerException("Recursive variable definition for " . $name, $this->index, null, $this->file);
		}

		$this->evaluating = true;

        $callback = function ($frame) use ($env, $name) {
            if ($v = $frame->variable($name)) {
                return $v->value->compile($env);
            }
        };


        if ($variable = \Less\Environment::find($env->frames, $callback)) {
			$this->evaluating = false;
            return $variable;
        } else {
			throw new \Less\Exception\CompilerException("variable " . $name . " is undefined", $this->index, null, $this->file);
        }
    }
}
