<?php

namespace Less\Node;

class Expression {

	public $value;
	public $parens = false;
	public $parensInOp = false;

	public function __construct($value) {
		$this->value = $value;
	}

	public function compile($env) {

		if( $this->parens && !$this->parensInOp ){
			$env->parensStack[] = true;
		}

		if (is_array($this->value) && count($this->value) > 1) {

			$ret = array();
			foreach($this->value as $e){
				$ret[] = $e->compile($env);
			}
			$returnValue = new \Less\Node\Expression($ret);

		} else if (is_array($this->value) && count($this->value) == 1) {

			if( !isset($this->value[0]) ){
				$this->value = array_slice($this->value,0);
			}

			$returnValue = $this->value[0]->compile($env);
		} else {
			$returnValue = $this;
		}


		if( $this->parens && !$this->parensInOp ){
			array_pop($env->parensStack);
		}
		if( $this->parens && $this->parensInOp && !count($env->parensStack) ){
			$returnValue = new \Less\Node\Paren($returnValue);
		}
		return $returnValue;
	}

	public function toCSS ($env) {

		$ret = array();
		foreach($this->value as $e){
			$ret[] = method_exists($e, 'toCSS') ? $e->toCSS($env) : '';
		}

		return implode(' ',$ret);
	}

}
