<?php


class Less_Tree_Keyword extends Less_Tree{

	public $value;
	public $type = 'Keyword';

	/**
	 * @param string $value
	 */
	public function __construct($value=null){
		$this->value = $value;
	}

	public function compile(){
		return $this;
	}

	public function genCSS( $output ){
		$output->add( $this->value );
	}

	public function compare($other) {
		if ($other instanceof Less_Tree_Keyword) {
			return $other->value === $this->value ? 0 : 1;
		} else {
			return -1;
		}
	}
}
