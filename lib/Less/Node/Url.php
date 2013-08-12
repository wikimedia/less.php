<?php

namespace Less\Node;

class Url
{
    public $attrs;
    public $value;
    public $paths;

    public function __construct($value, $paths)
    {
		$this->value = $value;
		$this->paths = $paths;
    }
    public function toCSS()
    {
        return "url(" + $this->value->toCSS() + ")";
    }

    public function compile($ctx)
    {
		$val = $this->value->compile($ctx);

		return new \Less\Node\URL($val, $this->paths);
    }

}
