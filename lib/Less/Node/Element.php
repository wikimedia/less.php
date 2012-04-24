<?php

namespace Less\Node;

class Element
{
	public $combinator;
    public $value;
	public $index;

    public function __construct($combinator, $value, $index = null) {
        if ( ! ($combinator instanceof \Less\Node\Combinator)) {
            $combinator = new Combinator($combinator);
        }

		if (is_string($value)) {
			$this->value = trim($value);
		} elseif ($value) {
			$this->value = $value;
		} else {
			$this->value = "";
		}

        $this->combinator = $combinator;
		$this->index = $index;
    }

    public function toCSS ($env) {
        return $this->combinator->toCSS($env) . (is_string($this->value) ? $this->value : $this->value->toCSS($env));
    }

	public function compile($env) {
		return new Element($this->combinator,
			is_string($this->value) ? $this->value : $this->value->compile($env),
			$this->index
		);
	}
}
