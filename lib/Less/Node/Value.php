<?php

namespace Less\Node;

class Value
{
    public function __construct($value)
    {
        $this->value = $value;
        $this->is = 'value';
    }

    public function compile($env)
    {
        if (count($this->value) == 1) {
            return $this->value[0]->compile($env);
        } else {
            return new \Less\Node\Value(array_map(function ($v) use ($env) {
                return $v->compile($env);
            }, $this->value));
        }
    }

    public function toCSS ($env){

		$ret = array();
		foreach($this->value as $e){
			$ret[] = $e->toCSS($env);
		}
		return implode($env->compress ? ',' : ', ', $ret);
    }
}
