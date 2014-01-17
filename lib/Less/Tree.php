<?php

class Less_Tree{

	public function toCSS($env = null){
		$output = new Less_Output();
		$this->genCSS($env, $output);
		return $output->toString();
	}

	public static function OutputAdd( $output, $chunk, $fileInfo = null, $index = null ){
		$output[] = $chunk;
	}


	public static function outputRuleset($env, $output, $rules ){

		$ruleCnt = count($rules);
		$env->tabLevel++;


		// Compressed
		if( Less_Environment::$compress ){
			$output->add('{');
			for( $i = 0; $i < $ruleCnt; $i++ ){
				$rules[$i]->genCSS( $env, $output );
			}

			$output->add( '}' );
			$env->tabLevel--;
			return;
		}


		// Non-compressed
		$tabSetStr = "\n".str_repeat( '  ' , $env->tabLevel-1 );
		$tabRuleStr = $tabSetStr.'  ';

		$output->add( " {" );
		for($i = 0; $i < $ruleCnt; $i++ ){
			$output->add( $tabRuleStr );
			$rules[$i]->genCSS( $env, $output );
		}
		$env->tabLevel--;
		$output->add( $tabSetStr.'}' );

	}

	public function accept($visitor){}

	/**
	 * Requires php 5.3+
	 */
	public static function __set_state($args){

		$class = get_called_class();
		$obj = new $class(null,null,null,null);
		foreach($args as $key => $val){
			$obj->$key = $val;
		}
		return $obj;
	}

}