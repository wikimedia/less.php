<?php


class Less_Tree_Combinator extends Less_Tree{

	public $value;
	public $type = 'Combinator';

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

    /**
     * @see Less_Tree::genCSS
     */
	function genCSS( $output ){
		if( Less_Environment::$compress ){
			$output->add( self::$_outputMapCompressed[$this->value] );
		}else{
			$output->add( self::$_outputMap[$this->value] );
		}
	}

}
