<?php

namespace Less\Node;

class Expression {

	public $value;

	public function __construct($value) {
		$this->value = $value;
	}

	public function compile($env) {
		if (is_array($this->value) && count($this->value) > 1) {

			$ret = array();
			foreach($this->value as $e){
				$ret[] = $e->compile($env);
			}
			return new \Less\Node\Expression($ret);

		} else if (is_array($this->value) && count($this->value) == 1) {

			if( !isset($this->value[0]) ){
				$this->value = array_slice($this->value,0);
			}

			return $this->value[0]->compile($env);
		} else {
			return $this;
		}
	}

	public function toCSS ($env) {

		$ret = array();
		foreach($this->value as $e){
			$ret[] = method_exists($e, 'toCSS') ? $e->toCSS($env) : '';
		}

		return implode(' ',$ret);
	}

}
