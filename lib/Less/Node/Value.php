<?php

namespace Less\Node;

class Value{

	public $type = 'Value';

	public function __construct($value){
		$this->value = $value;
		$this->is = 'value';
	}

	function accept($visitor) {
		$this->value = $visitor->visit($this->value);
	}

	public function compile($env){

		if( count($this->value) == 1 ){
			return $this->value[0]->compile($env);
		}

		$ret = array();
		foreach($this->value as $v){
			$ret[] = $v->compile($env);
		}

		return new \Less\Node\Value($ret);
	}

	public function toCSS ($env){

		$ret = array();
		foreach($this->value as $e){
			$ret[] = $e->toCSS($env);
		}
		return implode($env->compress ? ',' : ', ', $ret);
	}
}
