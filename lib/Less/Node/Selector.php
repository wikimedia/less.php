<?php


class Less_Tree_Selector extends Less_Tree{

	public $elements;
	public $extendList = array();
	private $_css;

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
		$visitor->visit($this->elements);
		$visitor->visit($this->extendList);
		$visitor->visit($this->condition);
	}

	function createDerived( $elements, $extendList, $evaldCondition ){
		$evaldCondition = $evaldCondition != null ? $evaldCondition : $this->evaldCondition;
		$newSelector = new Less_Tree_Selector( $elements, ($extendList ? $extendList : $this->extendList), $this->condition, $this->index, $this->currentFileInfo, $this->isReferenced);
		$newSelector->evaldCondition = $evaldCondition;
		return $newSelector;
	}

	public function match($other) {
		global $debug;
		$len   = count($this->elements);

		$olen = $offset = 0;
		if( $other && count($other->elements) ){

			if( $other->elements[0]->value === "&" ){
				$offset = 1;
			}
			$olen = count($other->elements) - $offset;
		}

		if( $olen === 0 || $len < $olen ){
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

		if( (!$env || !$env->firstSelector) && $this->elements[0]->combinator->value === "" ){
			self::OutputAdd( $strs, ' ', $this->currentFileInfo, $this->index );
		}
		if( !$this->_css ){
			//TODO caching? speed comparison?
			for($i = 0; $i < count($this->elements); $i++ ){
				$element = $this->elements[$i];
				$element->genCSS( $env, $strs );
			}
		}
	}

	function markReferenced(){
		$this->isReferenced = true;
	}

	function getIsReferenced(){
		return !$this->currentFileInfo['reference'] || $this->isReferenced;
	}

	function getIsOutput(){
		return $this->evaldCondition;
	}

}
