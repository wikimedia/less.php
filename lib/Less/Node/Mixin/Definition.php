<?php

namespace Less\Node\Mixin;

class Definition extends \Less\Node\Ruleset
{
    public function __construct($name, $params, $rules, $filename, $line)
    {
        $this->line = $line;
        $this->filename = $filename;
        $this->name = $name;
        $this->selectors = array(new \Less\Node\Selector(array( new \Less\Node\Element(null, $name))));
        $this->params = $params;
        $this->arity = count($params);
        $this->rules = $rules;
        $this->lookups = array();
        $this->required = array_reduce($params, function ($count, $p) {
            if (! isset($p['name']) || ($p['name'] && !isset($p['value']))) {
                return $count + 1;
            } else {
                return $count;
            }
        });
        $this->frames = array();
    }

    public function toCss($context, $env)
    {
        return '';
    }

    public function compile($env, $args = array())
    {
        $frame = new \Less\Node\Ruleset(null, array());

        foreach($this->params as $i => $param) {
            if (isset($param['name']) && $param['name']) {
                if ($val = (isset($args[$i]) ? $args[$i] : $param['value'])) {
                    $rule = new \Less\Node\Rule($param['name'], $val->compile($env));
                    array_unshift($frame->rules, $rule);
                } else {
                    throw new \Less\Exception\CompilerException("wrong number of arguments for " . $this->name . ' (' . count($args) . ' for ' . $this->arity . ')');
                }
            }
        }
        $_arguments = array();
        for ($i = 0; $i < max(count($this->params), count($args)); $i++) {
            $_arguments[] = isset($args[$i]) ? $args[$i] : $this->params[$i]['value'];
        }
        $ex = new \Less\Node\Expression($_arguments);
        array_unshift($frame->rules, new \Less\Node\Rule('@arguments', $ex->compile($env)));

        // duplicate the environment, adding new frames.
        $ruleSetEnv = new \Less\Environment();
        $ruleSetEnv->addFrame($this);
        $ruleSetEnv->addFrame($frame);
        $ruleSetEnv->addFrames($this->frames);
        $ruleSetEnv->addFrames($env->frames);
        $ruleset = new \Less\Node\Ruleset(null, $this->rules);

        return $ruleset->compile($ruleSetEnv);
    }

    public function match($args, $env = NULL)
    {
        if (count($args) < $this->required) {
            return false;
        }
        if (($this->required > 0) && (count($args) > count($this->params))) {
            return false;
        }

        $len = min(count($args), $this->arity);

        for ($i = 0; $i < $len; $i++) {
            if ( ! isset($this->params[$i]['name'])) {
                if ($args[$i]->compile($env)->toCSS() != $this->params[$i]['value']->compile($env)->toCSS()) {
                    return false;
                }
            }
        }

        return true;
    }

}
