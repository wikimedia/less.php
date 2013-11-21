<?php

class Less_Tree{

	public function toCSS($env){
		$strs = array();
		$this->genCSS($env, $strs );
		return implode('',$strs);
	}

	public static function OutputAdd( &$strs, $chunk, $fileInfo = null, $index = null ){
		$strs[] = $chunk;
	}


	public static function outputRuleset($env, &$strs, $rules ){

		self::OutputAdd( $strs, ($env->compress ? '{' : ' {\n') );

		if( !isset($env->tabLevel) ){
			$env->tabLevel = 0;
		}
		$env->tabLevel++;

		$tabRuleStr = $env->compress ? '' : str_repeat( '  ' , $env->tabLevel + 1 );
		$tabSetStr = $env->compress ? '' : str_repeat( '  ' , $env->tabLevel );

		for($i = 0; $i < count($rules); $i++ ){
			self::OutputAdd( $strs, $tabRuleStr );
			$rules[$i]->genCSS( $env, $strs );
			self::OutputAdd( $strs, ($env->compress ? '' : '\n') );
		}
		$env->tabLevel--;
		self::OutputAdd( $strs, $tabSetStr.'}' );
	}

}