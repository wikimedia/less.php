<?php

class Less_Tree_DefaultFunc{

	var $error_;
	var $value_;

	function compile(){
		if( $this->error_ ){
			throw Exception($this->error_);
		}
		if( $this->value_ != null ){
			return $this->value_ ? new Less_Tree_Keyword('true') : new Less_Tree_Keyword('false');
		}
	}

	function value( $v ){
		$this->value_ = $v;
	}

	function error( $e ){
		$this->error_ = $e;
	}

	function reset(){
		$this->value_ = $this->error_ = null;
	}
}