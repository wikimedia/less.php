<?php

namespace Less\Node;

class Url
{
    public $attrs;
    public $value;
    public $paths;

    public function __construct($value, $paths)
    {
        if (isset($value->data) && $value->data) {
            $this->attrs = $value;
        } else {
            $this->value = $value;
            $this->paths = $paths;
        }
    }
    public function toCSS()
    {
        return "url(" . ($this->attrs ? ('data:' . $this->attrs->mime . $this->attrs->charset . $this->attrs->base64 . $this->attrs->data) : $this->value->toCSS()) . ")";
    }

    public function compile($ctx)
    {
		if( $this->attrs ){
			return $this;
		}

		$val = $this->value->compile($ctx);

		return new \Less\Node\URL($val, $this->paths);
    }

}
