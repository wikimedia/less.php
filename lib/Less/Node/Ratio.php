<?php

// less.js :  lib/less/tree/ratio.js

namespace Less\Node;

class Ratio{

	public function __construct($value){
		$this->value = $value;
	}

	// prototype.eval
	public function compile($env = null) {
		return $this;
	}

	public function toCSS(){
		return $this->value;
	}

	public function __toString(){
		return $this->toCSS();
	}

}
