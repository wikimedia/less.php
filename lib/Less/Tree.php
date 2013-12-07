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

		$ruleCnt = count($rules);
		$env->tabLevel++;


		// Compressed
		if( $env->compress ){
			self::OutputAdd( $strs, '{' );
			for( $i = 0; $i < $ruleCnt; $i++ ){
				$rules[$i]->genCSS( $env, $strs );
			}
			self::OutputAdd( $strs, '}' );
			$env->tabLevel--;
			return;
		}


		// Non-compressed
		$tabSetStr = "\n".str_repeat( '  ' , $env->tabLevel-1 );
		$tabRuleStr = $tabSetStr.'  ';

		self::OutputAdd( $strs, " {" );
		for($i = 0; $i < $ruleCnt; $i++ ){
			self::OutputAdd( $strs, $tabRuleStr );
			$rules[$i]->genCSS( $env, $strs );
		}
		$env->tabLevel--;
		self::OutputAdd( $strs, $tabSetStr.'}' );

	}

}