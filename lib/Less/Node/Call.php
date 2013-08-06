<?php

namespace Less\Node;

//
// A function call node.
//

class Call
{
    private $value;

    public function __construct($name, $args, $index)
    {
        $this->name = $name;
        $this->args = $args;
        $this->index = $index;
    }

    //
    // When evaluating a function call,
    // we either find the function in `tree.functions` [1],
    // in which case we call it, passing the  evaluated arguments,
    // or we simply print it out as it appeared originally [2].
    //
    // The *functions.js* file contains the built-in functions.
    //
    // The reason why we evaluate the arguments, is in the case where
    // we try to pass a variable to a function, like: `saturate(@color)`.
    // The function should receive the value, not the variable.
    //
    public function compile($env)
    {
        $args = array_map(function ($a) use($env) {
                              return $a->compile($env);
                          }, $this->args);

        $name = $this->name == '%' ? '_percent' : $this->name;

        if (method_exists($env, $name)) { // 1.
            try {
                return call_user_func_array(array($env, $name), $args);
            } catch (Exception $e) {
                throw \Less\FunctionCallError("error evaluating function `" . $this->name . "`", $this->index);
            }
        } else { // 2.

            return new \Less\Node\Anonymous($this->name .
                   "(" . implode(', ', array_map(function ($a) use ($env) { return $a->toCSS($env); }, $args)) . ")");
        }
    }

    public function toCSS ($env) {
        return $this->compile($env)->toCSS();
    }

}
