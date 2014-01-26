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

	public $_oelements;

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
		$newSelector = new Less_Tree_Selector( $elements, ($extendList ? $extendList : $this->extendList), $this->condition, $this->index, $this->currentFileInfo, $this->isReferenced);
		$newSelector->evaldCondition = $evaldCondition ? $evaldCondition : $this->evaldCondition;
		return $newSelector;
	}

	// Performance issues with 1.6.1
	// Compiling bootstrap almost doubled: from 4.5 seconds to 7.8 seconds
	public function match( $other ){

		$oelements = $other->GetElements();
		if( !$oelements ){
			return 0;
		}

		$olen = count($oelements);
		$len = count($this->elements);
		if( $len < $olen) {
			return 0;
		}

		for( $i = 0; $i < $olen; $i++ ){
			if( $this->elements[$i]->value !== $oelements[$i]) {
				return 0;
			}
		}

		return $olen; // return number of matched elements
	}


	public function GetElements(){

		if( !isset($this->_oelements) ){
			$this->_oelements = array();
			$css = '';
			foreach($this->elements as $v){
				$css .= $v->combinator;
				if( !is_object($v->value) ){
					$css .= $v->value;
					continue;
				}

				if( !property_exists($v->value,'value') || is_object($v->value->value) ){
					$css = '';
					break;
				}
				$css .= $v->value->value;
			}

			if( preg_match_all('/[,&#\.\w-](?:[\w-]|(?:\\\\.))*/', $css, $matches) ){
				$this->_oelements = $matches[0];

				if( $this->_oelements[0] === '&' ){
					array_shift($this->_oelements);
				}
			}
		}

		return $this->_oelements;
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
	function genCSS( $output, $firstSelector = false ){

		if( !$firstSelector && $this->elements[0]->combinator === "" ){
			$output->add(' ', $this->currentFileInfo, $this->index);
		}

		foreach($this->elements as $element){
			$element->genCSS( $output );
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
