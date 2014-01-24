<?php


class Less_Tree_Rule extends Less_Tree{

	public $name;
	public $value;
	public $important;
	public $merge;
	public $index;
	public $inline;
	public $variable;
	public $currentFileInfo;
	public $type = 'Rule';

	/**
	 * @param string $important
	 */
	public function __construct($name, $value = null, $important = null, $merge = null, $index = null, $currentFileInfo = null,  $inline = false){
		$this->name = $name;
		$this->value = ($value instanceof Less_Tree_Value) ? $value : new Less_Tree_Value(array($value));
		$this->important = $important ? ' ' . trim($important) : '';
		$this->merge = $merge;
		$this->index = $index;
		$this->currentFileInfo = $currentFileInfo;
		$this->inline = $inline;
		$this->variable = ($name[0] === '@');
	}

	function accept($visitor) {
		$this->value = $visitor->visitObj( $this->value );
	}

    /**
     * @see Less_Tree::genCSS
     */
	function genCSS( $output ){

		$output->add( $this->name . Less_Environment::$colon_space, $this->currentFileInfo, $this->index);
		try{
			$this->value->genCSS( $output);

		}catch( Exception $e ){
			$e->index = $this->index;
			$e->filename = $this->currentFileInfo['filename'];
			throw $e;
		}
		$output->add( $this->important . (($this->inline || (Less_Environment::$lastRule && Less_Environment::$compress)) ? "" : ";"), $this->currentFileInfo, $this->index);
	}

	public function compile ($env){

		$strictMathBypass = false;
		if( $this->name === "font" && !Less_Environment::$strictMath ){
			$strictMathBypass = true;
			Less_Environment::$strictMath = true;
		}

		$return = new Less_Tree_Rule($this->name,
									$this->value->compile($env),
									$this->important,
									$this->merge,
									$this->index,
									$this->currentFileInfo,
									$this->inline);

		if( $strictMathBypass ){
			Less_Environment::$strictMath = false;
		}

		return $return;
	}

	function makeImportant(){
		return new Less_Tree_Rule($this->name, $this->value, '!important', $this->merge, $this->index, $this->currentFileInfo, $this->inline);
	}

}
