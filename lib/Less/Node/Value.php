<?php


class Less_Tree_Value extends Less_Tree{

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

		return new Less_Tree_Value($ret);
	}

	function genCSS( $env, &$strs ){
		for($i = 0; $i < count($this->value); $i++ ){
			$this->value[$i]->genCSS( $env, $strs);
			if( $i+1 < count($this->value) ){
				self::OutputAdd( $strs, ($env && $env->compress) ? ',' : ', ' );
			}
		}
	}

}
