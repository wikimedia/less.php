<?php


class Less_Tree_Selector extends Less_Tree{

	public $elements;
	public $extendList = array();
	public $_css;
	public $index;
	public $evaldCondition = false;
	public $type = 'Selector';

	public function __construct($elements, $extendList=array() , $condition = null, $index=null, $currentFileInfo=array(), $isReferenced=null ){
		$this->elements = $elements;
		$this->extendList = $extendList;
		$this->condition = $condition;
		$this->currentFileInfo = $currentFileInfo;
		$this->isReferenced = $isReferenced;
		if( !$condition ){
			$this->evaldCondition = true;
		}
	}

	function accept($visitor) {
		$this->elements = $visitor->visit($this->elements);
		$this->extendList = $visitor->visit($this->extendList);
		$this->condition = $visitor->visit($this->condition);
	}

	function createDerived( $elements, $extendList = null, $evaldCondition = null ){
		$evaldCondition = $evaldCondition != null ? $evaldCondition : $this->evaldCondition;
		$newSelector = new Less_Tree_Selector( $elements, ($extendList ? $extendList : $this->extendList), $this->condition, $this->index, $this->currentFileInfo, $this->isReferenced);
		$newSelector->evaldCondition = $evaldCondition;
		return $newSelector;
	}

	public function match($other) {
		global $debug;

		if( !$other ){
			return 0;
		}

		$offset = 0;
		$olen = count($other->elements);
		if( $olen ){
			if( $other->elements[0]->value === "&" ){
				$offset = 1;
			}
			$olen -= $offset;
		}

		if( $olen === 0 ){
			return 0;
		}

		$len = count($this->elements);
		if( $len < $olen ){
			return 0;
		}

		$max = min($len, $olen);

		for ($i = 0; $i < $max; $i ++) {
			if ($this->elements[$i]->value !== $other->elements[$i + $offset]->value) {
				return 0;
			}
		}

		return $max; // return number of matched selectors
	}

	public function compile($env) {

		$elements = array();
		for( $i = 0, $len = count($this->elements); $i < $len; $i++){
			$elements[] = $this->elements[$i]->compile($env);
		}

		$extendList = array();
		for($i = 0, $len = count($this->extendList); $i < $len; $i++){
			$extendList[] = $this->extendList[$i]->compile($this->extendList[$i]);
		}

		$evaldCondition = false;
		if( $this->condition ){
			$evaldCondition = $this->condition->compile($env);
		}

		return $this->createDerived( $elements, $extendList, $evaldCondition );
	}

	function genCSS( $env, &$strs ){

		if( !Less_Environment::$firstSelector && $this->elements[0]->combinator->value === "" ){
			self::OutputAdd( $strs, ' ', $this->currentFileInfo, $this->index );
		}
		if( !$this->_css ){
			//TODO caching? speed comparison?
			foreach($this->elements as $element){
				$element->genCSS( $env, $strs );
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
