<?php

namespace Less\Node;

class Dimension
{
    public function __construct($value, $unit = false)
    {
        $this->value = floatval($value);
        $this->unit = $unit;
    }

    public function compile($env = null) {
        return $this;
    }

    public function toColor() {
        return new \Less\Node\Color(array($this->value, $this->value, $this->value));
    }

    public function toCSS()
    {
        return $this->value . $this->unit;
    }

    public function __toString()
    {
        return $this->toCSS();
    }

    // In an operation between two Dimensions,
    // we default to the first Dimension's unit,
    // so `1px + 2em` will yield `3px`.
    // In the future, we could implement some unit
    // conversions such that `100cm + 10mm` would yield
    // `101cm`.
    public function operate($op, $other)
    {
        return new \Less\Node\Dimension( \Less\Environment::operate($op, $this->value, $other->value), $this->unit ?: $other->unit);
    }
}
