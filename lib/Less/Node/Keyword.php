<?php

namespace Less\Node;

class Keyword
{
    public function __construct($value)
    {
        $this->value = $value;
    }

    public function toCss()
    {
        return $this->value;
    }

    public function compile($env)
    {
        return $this;
    }

	public function compare($other) {
		if ($other instanceof Keyword) {
			return $other->value === $this->value ? 0 : 1;
		} else {
			return -1;
		}
	}
}
