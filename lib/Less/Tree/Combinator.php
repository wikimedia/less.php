<?php


class Less_Tree_Combinator extends Less_Tree{

	public $value;
	public $firstCombinator;
	public $type = 'Combinator';

	public function __construct($value = null, $firstCombinator = false){
		$this->value = $value;
		$this->firstCombinator = $firstCombinator;
	}

	static $_outputMap = array(
		''  => '',
		' ' => ' ',
		':' => ' :',
		'+' => ' + ',
		'~' => ' ~ ',
		'>' => ' > ',
		'|' => '|',
        '^' => ' ^ ',
        '^^' => ' ^^ '
	);

	static $_outputMapCompressed = array(
		''  => '',
		' ' => ' ',
		':' => ' :',
		'+' => '+',
		'~' => '~',
		'>' => '>',
		'|' => '|',
        '^' => '^',
        '^^' => '^^'
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
