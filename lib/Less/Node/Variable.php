<?php

namespace Less\Node;

class Variable {

	public $type = 'Variable';
	public $name;
	public $index;
	public $currentFileInfo;
	private $evaluating = false;

    public function __construct($name, $index, $currentFileInfo = null) {
        $this->name = $name;
        $this->index = $index;
		$this->currentFileInfo = $currentFileInfo;
    }

	public function compile($env) {
		$name = $this->name;
		if (strpos($name, '@@') === 0) {
			$v = new \Less\Node\Variable(substr($name, 1), $this->index + 1);
			$name = '@' . $v->compile($env)->value;
		}

		if ($this->evaluating) {
			throw new \Less\Exception\CompilerException("Recursive variable definition for " . $name, $this->index, null, $this->currentFileInfo['file']);
		}

		$this->evaluating = true;


		foreach($env->frames as $frame){
			if( $v = $frame->variable($name) ){
				$this->evaluating = false;
				return $v->value->compile($env);
			}
		}

		throw new \Less\Exception\CompilerException("variable " . $name . " is undefined", $this->index, null);
	}

}
