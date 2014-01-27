<?php

//less.js : lib/less/tree/element.js

class Less_Tree_Element extends Less_Tree{

	public $combinator;
	public $value = '';
	public $index;
	public $currentFileInfo;
	public $type = 'Element';

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


	public function __construct($combinator, $value, $index = null, $currentFileInfo = null ){

		if( !is_null($value) ){
			$this->value = $value;
		}

		$this->combinator = $combinator;
		$this->index = $index;
		$this->currentFileInfo = $currentFileInfo;
	}

	function accept( $visitor ){
		if( is_object($this->value) ){ //object or string
			$this->value = $visitor->visitObj( $this->value );
		}
	}

	public function compile($env){

		if( is_string($this->value) ){
			return $this;
		}

		return new Less_Tree_Element($this->combinator, $this->value->compile($env), $this->index, $this->currentFileInfo );
	}

    /**
     * @see Less_Tree::genCSS
     */
	public function genCSS( $output ){
		$output->add( $this->toCSS(), $this->currentFileInfo, $this->index );
	}

	public function toCSS(){

		$value = $this->value;
		if( !is_string($value) ){
			$value = $value->toCSS();
		}

		if( $value === '' && $this->combinator && $this->combinator === '&' ){
			return '';
		}

		return Less_Tree_Element::$_outputMap[$this->combinator] . $value;
	}

}
