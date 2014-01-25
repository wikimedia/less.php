<?php


class Less_Tree_Selector extends Less_Tree{

	public $elements;
	public $condition;
	public $extendList = array();
	public $_css;
	public $index;
	public $evaldCondition = false;
	public $type = 'Selector';
	public $currentFileInfo = array();
	public $isReferenced;

	public $elements_len = 0;

	/**
	 * @param boolean $isReferenced
	 */
	public function __construct($elements = null, $extendList=null , $condition = null, $index=null, $currentFileInfo=null, $isReferenced=null ){



		$this->elements = $elements;
		$this->elements_len = count($elements);
		if( $extendList ){
			$this->extendList = $extendList;
		}
		$this->condition = $condition;
		if( $currentFileInfo ){
			$this->currentFileInfo = $currentFileInfo;
		}
		$this->isReferenced = $isReferenced;
		if( !$condition ){
			$this->evaldCondition = true;
		}
	}

	function accept($visitor) {
		$this->elements = $visitor->visitArray($this->elements);
		$this->extendList = $visitor->visitArray($this->extendList);
		if( $this->condition ){
			$this->condition = $visitor->visitObj($this->condition);
		}
	}

	function createDerived( $elements, $extendList = null, $evaldCondition = null ){
		$evaldCondition = $evaldCondition != null ? $evaldCondition : $this->evaldCondition;
		$newSelector = new Less_Tree_Selector( $elements, ($extendList ? $extendList : $this->extendList), $this->condition, $this->index, $this->currentFileInfo, $this->isReferenced);
		$newSelector->evaldCondition = $evaldCondition;
		return $newSelector;
	}

	public function match($other) {

		$css = $other->toCSS();
		if( !preg_match_all('#[,&\#\.\w-](?:[\w-]|(?:\\\\.))*#', $css, $matches) ){
			return 0;
		}

		$oelements = $matches[0];
		if( !$oelements ){
			return 0;
		}

		if( $oelements[0] === '&' ){
			array_shift($oelements);
		}

		$olen = count($oelements);
		$len = count($this->elements);
		if( $olen === 0 || $len < $olen) {
			return 0;
		}

		for( $i = 0; $i < $olen; $i++ ){
			if( $this->elements[$i]->value !== $oelements[$i]) {
				return 0;
			}
		}

		return $olen; // return number of matched elements
	}


	public function compile($env) {

		$elements = array();
		foreach($this->elements as $el){
			$elements[] = $el->compile($env);
		}

		$extendList = array();
		foreach($this->extendList as $el){
			$extendList[] = $el->compile($el);
		}

		$evaldCondition = false;
		if( $this->condition ){
			$evaldCondition = $this->condition->compile($env);
		}

		return $this->createDerived( $elements, $extendList, $evaldCondition );
	}

    /**
     * @see Less_Tree::genCSS
     */
	function genCSS( $output ){

		if( !Less_Environment::$firstSelector && $this->elements[0]->combinator->value === "" ){
			$output->add( ' ', $this->currentFileInfo, $this->index );
		}
		if( !$this->_css ){
			//TODO caching? speed comparison?
			foreach($this->elements as $element){
				$element->genCSS( $output );
			}
		}
	}

	function markReferenced(){
		$this->isReferenced = true;
	}

	function getIsReferenced(){
		return !isset($this->currentFileInfo['reference']) || !$this->currentFileInfo['reference'] || $this->isReferenced;
	}

	function getIsOutput(){
		return $this->evaldCondition;
	}

}
