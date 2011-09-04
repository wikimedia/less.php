<?php

namespace Less\Node\Mixin;

class Call
{
    public function __construct($elements, $args, $index)
    {
        $this->selector =  \Less\Node\Selector($elements);
        $this->arguments = $args;
        $this->index = $index;
    }

    public function compile($env)
    {
        $rules = array();
        $match = false;

        foreach($env->frames as $frame) {

            if ($mixins = $frame->find($this->selector)) {

                $args = array_map(function ($a) use ($env) {
                    return $a->compile($env);
                }, $this->arguments);

                foreach ($mixins as $mixin) {
                    if ($mixin->match($args, $env)) {
                        try {
                            $rules = $mixin->compile($env, $this->arguments)->rules; //TODO: Check how we return rules
                            $match = true;
                        } catch (Exception $e) {
                            throw new \Less\CompileError($e->message, $e->index);
                        }
                    }
                }
                if ($match) {
                    return $rules;
                } else {
                    throw new \Less\CompilerError('No matching definition was found for `'.
                                                  trim($this->selector->toCSS()) . '(' .
                                                  implode(', ', array_map(function ($a) {
                                                    return $a->toCss();
                                                  }, $this->arguments)) . ')`',
                                                  $this->index);
                }
            }
        }

        throw new \Less\CompilerError(trim($this->selector->toCSS()) . " is undefined", $this->index);
    }
}


