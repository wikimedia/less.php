<?php

class Less_Tree{

	public function toCSS($env){
		$strs = array();
		$this->genCSS($env, $strs );
		return implode('',$strs);
	}

	public static function toCSS_Add( &$strs, $chunk, $fileInfo = null, $index = null ){
		$strs[] = $chunk;
	}


	public static function outputRuleset($env, $strs, $rules ){

		self::toCSS_Add( $strs, ($env->compress ? '{' : ' {\n') );

		if( !isset($env->tabLevel) ){
			$env->tabLevel = 0;
		}
		$env->tabLevel++;

		$tabRuleStr = $env->compress ? '' : str_repeat( '  ' , $env->tabLevel + 1 );
		$tabSetStr = $env->compress ? '' : str_repeat( '  ' , $env->tabLevel );

		for($i = 0; $i < count($rules); $i++ ){
			self::toCSS_Add( $strs, $tabRuleStr );
			$rules[$i]->genCSS( $env, $strs );
			self::toCSS_Add( $strs, ($env->compress ? '' : '\n') );
		}
		$env->tabLevel--;
		self::toCSS_Add( $strs, $tabSetStr.'}' );
	}

}