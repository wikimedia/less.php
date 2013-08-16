<?php

namespace Less\Node;

class Quoted
{
    public $value;
    public $content;

    public function __construct($str, $content, $escaped = false, $i = false) {
        $this->escaped = $escaped;
        $this->value = $content ?: '';
        $this->quote = $str[0];
        $this->index = $i;
    }

    public function toCSS ()
    {
        if ($this->escaped) {
            return $this->value;
        } else {
            return $this->quote . $this->value . $this->quote;
        }
    }

    public function compile($env)
    {
        $that = $this;
        $value = preg_replace_callback('/`([^`]+)`/', function ($matches) use ($env, $that) {
                    $js = \Less\Node\JavaScript($matches[1], $that->index, true);
                    return $js->eval($env)->value;
                 }, $this->value);
        $value = preg_replace_callback('/@\{([\w-]+)\}/', function ($matches) use ($env, $that) {
                    $v = new \Less\Node\Variable('@' . $matches[1], $that->index);
                    $v = $v->compile($env);
                    return ($v instanceof \Less\Tree\Quoted) ? $v->value : $v->toCSS($env);
                 }, $value);

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
