<?php

namespace Less\Node;

class Quoted
{
    public $escaped;
    public $content;

    public function __construct($str, $content, $escaped = false, $i = false)
    {
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
                    return isset($v->value) ? $v->value : $v->toCSS();
                 }, $value);

        return new \Less\Node\Quoted($this->quote . $value . $this->quote, $value, $this->escaped, $this->index);
    }
}
