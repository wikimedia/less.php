<?php


class Less_Tree_Combinator extends Less_Tree{

	public $value;

	public function __construct($value = null) {
		if( $value == ' ' ){
			$this->value = ' ';
		}else {
			$this->value = trim($value);
		}
	}

	static $_outputMap = array(
		''  => '',
		' ' => ' ',
		':' => ' :',
		'+' => ' + ',
		'~' => ' ~ ',
		'>' => ' > ',
		'|' => '|'
	);

	static $_outputMapCompressed = array(
		''  => '',
		' ' => ' ',
		':' => ' :',
		'+' => '+',
		'~' => '~',
		'>' => '>',
		'|' => '|'
	);

	function genCSS($env, &$strs ){
		if( $env->compress ){
			self::toCSS_Add( $strs, self::$_outputMapCompressed[$this->value] );
		}else{
			self::toCSS_Add( $strs, self::$_outputMap[$this->value] );
		}
	}

}
