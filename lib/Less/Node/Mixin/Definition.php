<?php

namespace Less\Node\Mixin;

class Definition extends \Less\Node\Ruleset
{
	public $name;
	public $selectors;
	public $params;
	public $arity;
	public $rules;
	public $lookups;
	public $required;
	public $frames;
	public $condition;
	public $variadic;

	// less.js : /lib/less/tree/mixin.js : tree.mixin.Definition
    public function __construct($name, $params, $rules, $condition, $variadic = false)
    {
        $this->name = $name;
        $this->selectors = array(new \Less\Node\Selector(array( new \Less\Node\Element(null, $name))));
        $this->params = $params;
		$this->condition = $condition;
		$this->variadic = $variadic;
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

	// less.js : /lib/less/tree/mixin.js : tree.mixin.Definition.evalParams
    public function compileParams($env, $args = array())
    {
        $frame = new \Less\Node\Ruleset(null, array());


        foreach($this->params as $i => $param) {

			$arg = null;
			if( array_key_exists($i,$args) && $args[$i] ){
				$arg = $args[$i];
			}

			if( $arg && $arg['name'] ){
				array_unshift($frame->rules, new \Less\Node\Rule($arg['name'], $arg['value']->compile($env) ));
				array_splice($args,$i,1);
				$i--;
                continue;
			}


            if (isset($param['name']) && $param['name']) {
				if (isset($param['variadic']) && $param['variadic'] && $args) {
					$varargs = array();
					for ($j = $i; $j < count($args); $j++) {
						$varargs[] = $args[$j]['value']->compile($env);
					}
					$expression = new \Less\Node\Expression($varargs);
					array_unshift($frame->rules, new \Less\Node\Rule($param['name'], $expression->compile($env)));

				} elseif ( $val = ($arg && $arg['value'] ? $arg['value'] : $param['value']) ){
					array_unshift($frame->rules, new \Less\Node\Rule($param['name'], $val->compile($env)));
				} else {
					throw new \Less\Exception\CompilerException("Wrong number of arguments for " . $this->name . " (" . count($args) . ' for ' . $this->arity . ")");
				}
            }
        }
		return $frame;
	}

	// less.js : /lib/less/tree/mixin.js : tree.mixin.Definition.eval
	public function compile($env, $args, $important) {
		$frame = $this->compileParams($env, $args);

		$_arguments = array();
        for ($i = 0; $i < max(count($this->params), count($args)); $i++) {

            if( isset($args[$i]) && $args[$i]['value'] ){
				$_arguments[] = $args[$i]['value'];
			}else{
				$_arguments[] = $this->params[$i]['value'];
			}

        }
        $ex = new \Less\Node\Expression($_arguments);
        array_unshift($frame->rules, new \Less\Node\Rule('@arguments', $ex->compile($env)));

		$rules = $important
			? array_map(function($r) {
					return new \Less\Node\Rule($r->name, $r->value, '!important', $r->index);
				}, $this->rules)
			: array_slice($this->rules, 0);

        // duplicate the environment, adding new frames.
        $ruleSetEnv = new \Less\Environment();
        $ruleSetEnv->addFrame($this);
        $ruleSetEnv->addFrame($frame);
        $ruleSetEnv->addFrames($this->frames);
        $ruleSetEnv->addFrames($env->frames);
        $ruleset = new \Less\Node\Ruleset(null, $rules);

        return $ruleset->compile($ruleSetEnv);
    }

	// less.js : /lib/less/tree/mixin.js : tree.mixin.Definition.match
    public function match($args, $env = NULL)
    {
		if (!$this->variadic) {
			if (count($args) < $this->required)
				return false;
			if (count($args) > count($this->params))
				return false;
			if (($this->required > 0) && (count($args) > count($this->params)))
				return false;
		}

        // duplicate the environment, adding new frames.
        $conditionEnv = new \Less\Environment();
        $conditionEnv->addFrame($this->compileParams($env, $args));
        $conditionEnv->addFrames($env->frames);

		if ($this->condition && !$this->condition->compile($conditionEnv)) {
			return false;
		}

        $len = min(count($args), $this->arity);

        for ($i = 0; $i < $len; $i++) {
            if ( ! isset($this->params[$i]['name'])) {
                if ($args[$i]['value']->compile($env)->toCSS() != $this->params[$i]['value']->compile($env)->toCSS()) {
                    return false;
                }
            }
        }

        return true;
    }

}
