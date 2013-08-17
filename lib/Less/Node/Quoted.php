<?php

namespace Less\Node;

class Quoted{
	public $value;
	public $content;

	public function __construct($str, $content, $escaped = false, $i = false) {
		$this->escaped = $escaped;
		$this->value = $content ?: '';
		$this->quote = $str[0];
		$this->index = $i;
	}

	public function toCSS (){
		if ($this->escaped) {
			return $this->value;
		} else {
			return $this->quote . $this->value . $this->quote;
		}
	}

	public function compile($env){
		$that = $this;

		$value = $this->value;
		if( preg_match_all('/`([^`]+)`/', $this->value, $matches) ){
			foreach($matches as $i => $match){
				$js = \Less\Node\JavaScript($matches[1], $that->index, true);
				$js = $js->compile($env)->value;
				$value = str_replace($matches[0][$i], $js, $value);
			}
		}

		if( preg_match_all('/@\{([\w-]+)\}/',$value,$matches) ){
			foreach($matches[1] as $i => $match){
				$v = new \Less\Node\Variable('@' . $match, $that->index);
				$v = $v->compile($env,true);
				$v = ($v instanceof \Less\Node\Quoted) ? $v->value : $v->toCSS($env);
				$value = str_replace($matches[0][$i], $v, $value);
			}
		}

		return new \Less\Node\Quoted($this->quote . $value . $this->quote, $value, $this->escaped, $this->index);
	}

	function compare($x) {

		if( !method_exists($x, 'toCSS') ){
			return -1;
		}

		$left = $this->toCSS();
		$right = $x->toCSS();

		if ($left === $right) {
			return 0;
		}

		return $left < $right ? -1 : 1;
	}
}
