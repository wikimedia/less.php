<?php

class Less_Tree{

	public function toCSS($env = null){
		$strs = array();
		$this->genCSS($env, $strs );
		return implode('',$strs);
	}

	public static function OutputAdd( &$strs, $chunk, $fileInfo = null, $index = null ){
		$strs[] = $chunk;
	}


	public static function outputRuleset($env, &$strs, $rules ){

		self::OutputAdd( $strs, ($env->compress ? '{' : " {\n") );

		$env->tabLevel++;

		$tabRuleStr = $tabSetStr = '';
		if( !$env->compress && $env->tabLevel ){
			$tabRuleStr = str_repeat( '  ' , $env->tabLevel );
			$tabSetStr = str_repeat( '  ' , $env->tabLevel-1 );
		}

		for($i = 0; $i < count($rules); $i++ ){
			self::OutputAdd( $strs, $tabRuleStr );
			$rules[$i]->genCSS( $env, $strs );
			self::OutputAdd( $strs, ($env->compress ? '' : "\n") );
		}
		$env->tabLevel--;
		self::OutputAdd( $strs, $tabSetStr.'}' );
	}

}