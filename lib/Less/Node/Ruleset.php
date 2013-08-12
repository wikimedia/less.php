<?php

//less.js : /lib/less/tree/ruleset.js

namespace Less\Node;

class Ruleset
{
	protected $lookups;
	private $_variables;
	private $_rulesets;

	public $strictImports;

	public $selectors;
	public $rules;
	public $root;
	public $allowImports;

	public function __construct($selectors, $rules, $strictImports = false)
	{
		$this->selectors = $selectors;
		$this->rules = (array) $rules;
		$this->lookups = array();
		$this->strictImports = $strictImports;
	}

	public function compile($env) {
		$selectors = $this->selectors ? array_map(function($s) use ($env) {
			return $s->compile($env);
		}, $this->selectors) : array();
		$ruleset = new Ruleset($selectors, $this->rules, $this->strictImports);
		$rules = array();

		$ruleset->root = $this->root;
		$ruleset->allowImports = $this->allowImports;

		// push the current ruleset to the frames stack
		$env->unshiftFrame($ruleset);

		// Evaluate imports
		if ($ruleset->root || $ruleset->allowImports || !$ruleset->strictImports) {
			foreach($ruleset->rules as $rule){
				if( $rule instanceof \Less\Node\Import  ){
                    $rules = array_merge($rules, $rule->compile($env));
                } else {
                    $rules[] = $rule;
                }
			}

            $ruleset->rules = $rules;
            $rules = array();
		}

		// Store the frames around mixin definitions,
		// so they can be evaluated like closures when the time comes.
		foreach($ruleset->rules as $i => $rule) {
			if ($rule instanceof \Less\Node\Mixin\Definition) {
				$ruleset->rules[$i]->frames = $env->frames;
			}
		}

		$mediaBlockCount = 0;
		if( $env instanceof \Less\Environment ){
			$mediaBlockCount = count($env->mediaBlocks);
		}

		// Evaluate mixin calls.
		foreach($ruleset->rules as $rule){
			if( $rule instanceof \Less\Node\Mixin\Call ){
                $rules = array_merge($rules, $rule->compile($env));
            } else {
				$rules[] = $rule;
            }
        }
        $ruleset->rules = $rules;

		for($i = 0; $i < count($ruleset->rules); $i++) {
			if (isset($ruleset->rules[$i]) && $ruleset->rules[$i] instanceof \Less\Node\Mixin\Call) {
				$newRules = $ruleset->rules[$i]->compile($env);
				$ruleset->rules = array_merge(
					array_slice($ruleset->rules, 0, $i),
					$newRules,
					array_slice($ruleset->rules, $i + 1)
				);
			}
		}

		// Evaluate everything else
		foreach($ruleset->rules as $i => $rule) {
			if (! ($rule instanceof \Less\Node\Mixin\Definition)) {
				$ruleset->rules[$i] = is_string($rule) ? $rule : $rule->compile($env);
			}
		}

		// Pop the stack
		$env->shiftFrame();

        if ($mediaBlockCount) {
			foreach($env->mediaBlocks as $mediaBlock){
				$mediaBlock->bubbleSelectors( $selectors );
            }
        }

		return $ruleset;
	}

	public function match($args)
	{
		return ! is_array($args) || count($args) === 0;
	}

	public function variables() {
		if ( ! $this->_variables) {
			$this->_variables = array_reduce($this->rules, function ($hash, $r) {
				if ($r instanceof \Less\Node\Rule && $r->variable === true) {
					$hash[$r->name] = $r;
				}
				return $hash;
			});
		}

		return $this->_variables;
	}

	public function variable($name)
	{
		$vars = $this->variables();

		return isset($vars[$name]) ? $vars[$name] : null;
	}

	public function rulesets ()
	{
		if ($this->_rulesets) {
			return $this->_rulesets;
		} else {
			return $this->_rulesets = array_filter($this->rules, function ($r) {
				return ($r instanceof \Less\Node\Ruleset) || ($r instanceof \Less\Node\Mixin\Definition);
			});
		}
	}

	public function find ($selector, $self = null, $env = null)
	{
		$self = $self ?: $this;
		$rules = array();
		$key = $selector->toCSS($env);

		if (array_key_exists($key, $this->lookups)) {
			return $this->lookups[$key];
		}

		foreach($this->rulesets() as $rule) {
			if ($rule !== $self) {
				foreach($rule->selectors as $ruleSelector) {
					if ($selector->match($ruleSelector)) {

						if (count($selector->elements) > count($ruleSelector->elements)) {
							$rules = array_merge($rules, $rule->find( new \Less\Node\Selector(array_slice($selector->elements, 1)), $self, $env));
						} else {
							$rules[] = $rule;
						}
						break;
					}
				}
			}
		}

		$this->lookups[$key] = $rules;

		return $this->lookups[$key];
	}

	//
	// Entry point for code generation
	//
	//	 `context` holds an array of arrays.
	//
	public function toCSS($context, $env)
	{
		$css = array();	  // The CSS output
		$rules = array();	// node.Rule instances
		$_rules = array();
		$rulesets = array(); // node.Ruleset instances
		$paths = array();	// Current selectors

		if (! $this->root) {
			$this->joinSelectors($paths, $context, $this->selectors);
		}

		// Compile rules and rulesets
		foreach($this->rules as $rule) {
			if (isset($rule->rules) || ($rule instanceof \Less\Node\Directive) || ($rule instanceof \Less\Node\Media)) {
				$rulesets[] = $rule->toCSS($paths, $env);
			} else if ($rule instanceof \Less\Node\Comment) {
				if (!$rule->silent) {
					if ($this->root) {
						$rulesets[] = $rule->toCSS($env);
					} else {
						$rules[] = $rule->toCSS($env);
					}
				}
			} else {
				if (method_exists($rule, 'toCSS') && ( ! isset($rule->variable) ||  ! $rule->variable)) {
					$rules[] = $rule->toCSS($env);
				} else if (isset($rule->value) && $rule->value && ! $rule->variable) {
					$rules[] = (string) $rule->value;
				}
			}
		}

		$rulesets = implode('', $rulesets);

		// If this is the root node, we don't render
		// a selector, or {}.
		// Otherwise, only output if this ruleset has rules.
		if ($this->root) {
			$css[] = implode($env->compress ? '' : "\n", $rules);
		} else {
			if (count($rules)) {
				$selector = array_map(function ($p) use ($env) {
					return trim(implode('', array_map(function ($s) use ($env) {
						return $s->toCSS($env);
					}, $p)));
				}, $paths);

				$selector = implode($env->compress ? ',' : ",\n", $selector);

				// Remove duplicates
				for ($i = count($rules) - 1; $i >= 0; $i--) {
					if (array_search($rules[$i], $_rules) === FALSE) {
						array_unshift($_rules, $rules[$i]);
					}
				}
				$rules = $_rules;

				$css[] = $selector;
				$css[] = ($env->compress ? '{' : " {\n  ") .
						 implode($env->compress ? '' : "\n  ", $rules) .
						 ($env->compress ? '}' : "\n}\n");
			}
		}
		$css[] = $rulesets;

		return implode('', $css) . ($env->compress ? "\n" : '' );
	}

	public function joinSelectors (&$paths, $context, $selectors)
	{
		foreach($selectors as $selector) {
			$this->joinSelector($paths, $context, $selector);
		}
	}

	public function joinSelector (&$paths, $context, $selector){

		$hasParentSelector = false; $newSelectors; $el; $sel; $parentSel;
		$newSelectorPath; $afterParentJoin; $newJoinedSelector;
		$newJoinedSelectorEmpty; $lastSelector; $currentElements;
		$selectorsMultiplied;

		foreach($selector->elements as $el) {
			if( $el->value === '&') {
				$hasParentSelector = true;
			}
		}

		if( !$hasParentSelector ){
			if( count($context) > 0 ) {
				foreach($context as $context_el){
					$paths[] = array_merge($context_el, array($selector) );
				}
			}else {
				$paths[] = array($selector);
			}
			return;
		}

		// The paths are [[Selector]]
		// The first list is a list of comma seperated selectors
		// The inner list is a list of inheritance seperated selectors
		// e.g.
		// .a, .b {
		//   .c {
		//   }
		// }
		// == [[.a] [.c]] [[.b] [.c]]
		//

		// the elements from the current selector so far
		$currentElements = array();
		// the current list of new selectors to add to the path.
		// We will build it up. We initiate it with one empty selector as we "multiply" the new selectors
		// by the parents
		$newSelectors = array(array());


		foreach( $selector->elements as $el){

			// non parent reference elements just get added
			if( $el->value !== '&' ){
				$currentElements[] = $el;
			} else {
				// the new list of selectors to add
				$selectorsMultiplied = array();

				// merge the current list of non parent selector elements
				// on to the current list of selectors to add
				if( count($currentElements) > 0) {
					$this->mergeElementsOnToSelectors( $currentElements, $newSelectors);
				}

				// loop through our current selectors
				foreach($newSelectors as $sel){

					// if we don't have any parent paths, the & might be in a mixin so that it can be used
					// whether there are parents or not
					if( count($context) ){
						// the combinator used on el should now be applied to the next element instead so that
						// it is not lost
						if( count($sel) > 0 ){
							$sel[0]->elements = array_slice($sel[0]->elements,0);
							$sel[0]->elements[] = new \Less\Node\Element($el->combinator, '', 0); //new Element(el.Combinator,  ""));
						}
						$selectorsMultiplied[] = $sel;
					}
					else {
						// and the parent selectors
						foreach($context as $parentSel){
							// We need to put the current selectors
							// then join the last selector's elements on to the parents selectors

							// our new selector path
							$newSelectorPath = array();
							// selectors from the parent after the join
							$afterParentJoin = array();
							$newJoinedSelectorEmpty = true;

							//construct the joined selector - if & is the first thing this will be empty,
							// if not newJoinedSelector will be the last set of elements in the selector
							if ( count($sel) > 0) {
								$newSelectorPath = array_slice($sel,0);
								$lastSelector = array_pop($newSelectorPath);
								$newJoinedSelector = new \Less\Node\Selector( array_slice($lastSelector->elements,0) );
								$newJoinedSelectorEmpty = false;
							}
							else {
								$newJoinedSelector = new \Less\Node\Selector( array() );
							}

							//put together the parent selectors after the join
							if ( count($parentSel) > 1) {
								$afterParentJoin = array_merge($afterParentJoin, array_slice($parentSel,1) );
							}

							if ( count($parentSel) > 0) {
								$newJoinedSelectorEmpty = false;

								// join the elements so far with the first part of the parent
								$newJoinedSelector->elements[] = new \Less\Node\Element( $el->combinator, $parentSel[0]->elements[0]->value, 0 );

								$newJoinedSelector->elements = array_merge( $newJoinedSelector->elements, array_slice($parentSel[0]->elements, 1) );
							}

							if (!$newJoinedSelectorEmpty) {
								// now add the joined selector
								$newSelectorPath[] = $newJoinedSelector;
							}

							// and the rest of the parent
							$newSelectorPath = array_merge($newSelectorPath, $afterParentJoin);

							// add that to our new set of selectors
							$selectorsMultiplied[] = $newSelectorPath;
						}
					}
				}

				// our new selectors has been multiplied, so reset the state
				$newSelectors = $selectorsMultiplied;
				$currentElements = array();
			}
		}

		// if we have any elements left over (e.g. .a& .b == .b)
		// add them on to all the current selectors
		if( count($currentElements) > 0) {
			$this->mergeElementsOnToSelectors($currentElements, $newSelectors);
		}
		foreach( $newSelectors as $new_sel){
			$paths[] = $new_sel;
		}

	}

	function mergeElementsOnToSelectors( $elements, $selectors){
		$i; $sel;
		if ( count($selectors) == 0) {
			$selectors[] = array( new \Less\Node\Selector($elements) );
			return;
		}


		foreach( $selectors as $sel){

			// if the previous thing in sel is a parent this needs to join on to it
			if ( count($sel) > 0) {
				$last = count($sel)-1;
				$sel[ $last ] = new \Less\Node\Selector( array_merge( $sel[$last]->elements, $elements) );
			}else{
				$sel[] = new \Less\Node\Selector( $elements );
			}
		}
	}
}
