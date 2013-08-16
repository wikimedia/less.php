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
	public function compileParams($env, $mixinEnv, $args = array(), $evaldArguments = array() )
	{
		$frame = new \Less\Node\Ruleset(null, array());
		$varargs;
		$params = array_slice($this->params,0);
		$val;
		$name;
		$isNamedFound;

		$args = array_slice($args,0);

		foreach($args as $i => $arg){

			if( $arg && $arg['name'] ){
				$name = $arg['name'];
				$isNameFound = false;

				foreach($params as $j => $param){
					if (!$evaldArguments[$j] && $name === $params[$j]['name']) {
						$evaldArguments[$j] = $arg['value']->compile($env);
						array_unshift($frame->rules, new \Less\Node\Rule( $name, $arg['value']->compile($env) ) );
						$isNamedFound = true;
						break;
					}
				}
				if ($isNamedFound) {
					array_splice($args, $i, 1);
					$i--;
					continue;
				} else {
					throw new \Less\Exception\CompilerException("Named argument for " . $this->name .' '.$args[$i]['name'] . ' not found');
				}
			}
		}

		$argIndex = 0;
		foreach($params as $i => $param){

			if ( isset($evaldArguments[$i]) ) continue;

			$arg = null;
			if( array_key_exists($argIndex,$args) && $args[$argIndex] ){
				$arg = $args[$argIndex];
			}

			if (isset($param['name']) && $param['name']) {
				$name = $param['name'];

				if( isset($param['variadic']) && $args ){
					$varargs = array();
					for ($j = $argIndex; $j < count($args); $j++) {
						$varargs[] = $args[$j]['value']->compile($env);
					}
					$expression = new \Less\Node\Expression($varargs);
					array_unshift($frame->rules, new \Less\Node\Rule($param['name'], $expression->compile($env)));
				}else{
					$val = ($arg && $arg['value']) ? $arg['value'] : false;
					if ($val) {
						$val = $val->compile($env);
					} else if ( isset($param['value']) ) {
						$val = $param['value']->compile($mixinEnv);
					} else {
						throw new \Less\Exception\CompilerException("Wrong number of arguments for " . $this->name . " (" . count($args) . ' for ' . $this->arity . ")");
					}

					array_unshift($frame->rules, new \Less\Node\Rule($param['name'], $val));
					$evaldArguments[$i] = $val;
				}
			}

			if ( isset($param['variadic']) && $args) {
				for ($j = $argIndex; $j < count($args); $j++) {
					$evaldArguments[$j] = $args[$j]['value']->compile($env);
				}
			}
			$argIndex++;
		}

		return $frame;
	}

	// less.js : /lib/less/tree/mixin.js : tree.mixin.Definition.eval
	public function compile($env, $args, $important) {
		$_arguments = array();

		$mixinFrames = array_merge($this->frames, $env->frames);

		$mixinEnv = new \Less\Environment();
		$mixinEnv->addFrames($mixinFrames);
		$frame = $this->compileParams($env, $mixinEnv, $args, $_arguments);

		$ex = new \Less\Node\Expression($_arguments);
		array_unshift($frame->rules, new \Less\Node\Rule('@arguments', $ex->compile($env)));

		$rules = $important
			? \Less\Node\Ruleset::makeImportant($this->selectors, $this->rules)->rules
			: array_slice($this->rules, 0);

		// duplicate the environment, adding new frames.
		$ruleSetEnv = new \Less\Environment();
		$ruleSetEnv->addFrame($this);
		$ruleSetEnv->addFrame($frame);
		$ruleSetEnv->addFrames($mixinFrames);
		$ruleSetEnv->compress = $env->compress;
		$ruleset = new \Less\Node\Ruleset(null, $rules);
		$ruleset->originalRuleset = $this;

		return $ruleset->compile($ruleSetEnv);
	}


	public function matchCondition($args, $env) {

		$mixinEnv = new \Less\Environment();
		$mixinEnv->addFrames($this->frames);
		$mixinEnv->addFrames($env->frames);

		// duplicate the environment, adding new frames.
		$conditionEnv = new \Less\Environment();
		$conditionEnv->addFrame($this->compileParams($env, $mixinEnv, $args));
		$conditionEnv->addFrames($env->frames);

		if ($this->condition && !$this->condition->compile($conditionEnv)) {
			return false;
		}

		return true;
	}

	public function matchArgs($args, $env = NULL){
		if (!$this->variadic) {
			if (count($args) < $this->required)
				return false;
			if (count($args) > count($this->params))
				return false;
			if (($this->required > 0) && (count($args) > count($this->params)))
				return false;
		}

		$len = min(count($args), $this->arity);

		for ($i = 0; $i < $len; $i++) {
			if ( !isset($this->params[$i]['name']) && !isset($this->params[$i]['variadic'])  ) {
				if ($args[$i]['value']->compile($env)->toCSS() != $this->params[$i]['value']->compile($env)->toCSS()) {
					return false;
				}
			}
		}

		return true;
	}

}
