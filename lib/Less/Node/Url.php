<?php

namespace Less\Node;

class Url
{
    public $attrs;
    public $value;
    public $rootpath;

    public function __construct($value, $rootpath)
    {
		$this->value = $value;
		$this->rootpath = $rootpath;
    }
    public function toCSS()
    {
        return "url(" + $this->value->toCSS() + ")";
    }

    public function compile($ctx)
    {
		$val = $this->value->compile($ctx);

		return new \Less\Node\URL($val, $this->rootpath);
    }

}
