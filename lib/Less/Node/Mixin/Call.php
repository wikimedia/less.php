<?php

namespace Less\Node\Mixin;

class Call
{
    public function __construct($elements, $args, $index)
    {
        $this->selector =  new \Less\Node\Selector($elements);
        $this->arguments = $args;
        $this->index = $index;
    }

    public function compile($env)
    {
        $rules = array();
        $match = false;

        foreach($env->frames as $frame) {

            if ($mixins = $frame->find($this->selector, null, $env)) {

                $args = array_map(function ($a) use ($env) {
                    return $a->compile($env);
                }, $this->arguments);

                foreach ($mixins as $mixin) {
                    if ($mixin->match($args, $env)) {
                        try {
                            if ($env->getDebug() && isset($mixin->name)) {
                                $rules[] = new \Less\Node\Comment('/**** Start rules from `' . $mixin->name.'` (defined in `'.$mixin->filename.'` on line: ' . $mixin->line.') ****/', false);
                            }
                            $rules = array_merge($rules, $mixin->compile($env, $this->arguments)->rules);
                            if ($env->getDebug() && isset($mixin->name)) {
                                $rules[] = new \Less\Node\Comment('/**** End rules from `' . $mixin->name.'` ****/', false);
                            }
                            $match = true;
                        } catch (Exception $e) {
                            throw new \Less\Exception\CompilerException($e->message, $e->index);
                        }
                    }
                }
                if ($match) {
                    return $rules;
                } else {
                    throw new \Less\Exception\CompilerException('No matching definition was found for `'.
                                                  trim($this->selector->toCSS($env)) . '(' .
                                                  implode(', ', array_map(function ($a) use($env) {
                                                    return $a->toCss($env);
                                                  }, $this->arguments)) . ')`',
                                                  $this->index);
                }
            }
        }

        throw new \Less\Exception\CompilerException(trim($this->selector->toCSS($env)) . " is undefined", $this->index);
    }
}


