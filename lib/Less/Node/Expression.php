<?php

class Less_Tree_Expression {

	//public $type = 'Expression';
	public $value = array();
	public $parens = false;
	public $parensInOp = false;

	public function __construct($value) {
		$this->value = $value;
	}

	function accept( $visitor ){
		$this->value = $visitor->visit( $this->value );
	}

	public function compile($env) {

		$inParenthesis = $this->parens && !$this->parensInOp;
		$doubleParen = false;
		if( $inParenthesis ) {
			$env->inParenthesis();
		}

		if (is_array($this->value) && count($this->value) > 1) {

			$ret = array();
			foreach($this->value as $e){
				$ret[] = $e->compile($env);
			}
			$returnValue = new Less_Tree_Expression($ret);

		} else if (is_array($this->value) && count($this->value) == 1) {

			if( !isset($this->value[0]) ){
				$this->value = array_slice($this->value,0);
			}

			if( property_exists($this->value[0], 'parens') && $this->value[0]->parens && !$this->value[0]->parensInOp ){
				$doubleParen = true;
			}

			$returnValue = $this->value[0]->compile($env);
		} else {
			$returnValue = $this;
		}
		if( $inParenthesis ){
			$env->outOfParenthesis();
		}
		if( $this->parens && $this->parensInOp && !$env->isMathOn() && !$doubleParen ){
			$returnValue = new Less_Tree_Paren($returnValue);
		}
		return $returnValue;
	}

	function genCSS( $env, &$strs ){
		$val_len = count($this->value);
		for( $i = 0; $i < $val_len; $i++ ){
			$this->value[$i]->genCSS( $env, $strs );
			if( $i + 1 < $val_len ){
				self::OutputAdd( $strs, ' ' );
			}
		}
	}

	function throwAwayComments() {

		if( is_array($this->value) ){
			$new_value = array();
			foreach($this->value as $v){
				if( $v instanceof Less_Tree_Comment ){
					continue;
				}
				$new_value[] = $v;
			}
			$this->value = $new_value;
		}
	}
}
