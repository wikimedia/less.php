<?php

namespace Less\Node;

class Rule{
	public $type = 'Rule';
	public $name;
	public $value;
	public $important;
	public $index;
	public $inline;
	public $variable;

	public function __construct($name, $value, $important = null, $index = null, $inline = false){
		$this->name = $name;
		$this->value = ($value instanceof \Less\Node\Value) ? $value : new \Less\Node\Value(array($value));
		$this->important = $important ? ' ' . trim($important) : '';
		$this->index = $index;
		$this->inline = $inline;

		if ($name[0] === '@') {
			$this->variable = true;
		} else {
			$this->variable = false;
		}
	}

	function accept($visitor) {
		$this->value = $visitor->visit( $this->value );
	}

	public function toCSS ($env){
		if ($this->variable) {
			return "";
		} else {

			return $this->name . ($env->compress ? ':' : ': ') . $this->value->toCSS($env)
				. $this->important
				. ($this->inline ? "" : ";");
		}
	}

	public function compile ($env){

		$strictMathsBypass = false;
		if( $this->name === "font" && $env->strictMaths === false ){
			$strictMathsBypass = true;
			$env->strictMaths = true;
		}
		try {
			return new \Less\Node\Rule($this->name,
										$this->value->compile($env),
										$this->important,
										$this->index, $this->inline);
		}
		catch(\Exception $e){
			if( $strictMathsBypass ){
				$env->strictMaths = false;
			}
		}

	}

	function makeImportant(){
		return new \Less\Node\Rule($this->name, $this->value, '!important', $this->index, $this->inline);
	}

}
