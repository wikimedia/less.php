<?php

namespace Less\Node;

class Ruleset
{
    public $selectors;
    public $rules;
    protected $lookups;
    public $root;
    private $_variables;
    private $_rulesets;

    public function __construct($selectors, $rules)
    {
        $this->selectors = $selectors;
        $this->rules = (array) $rules;
        $this->lookups = array();

    }

    public function compile($env)
    {
        $ruleset = new Ruleset($this->selectors, $this->rules);
        $ruleset->root = $this->root;

        // push the current ruleset to the frames stack
        $env->unshiftFrame($ruleset);

        // Evaluate imports
        if ($ruleset->root) {
            for($i = 0; $i < count($ruleset->rules); $i++) {
                if ($ruleset->rules[$i] instanceof \Less\Node\Import && ! $ruleset->rules[$i]->css) {
                    $newRules = $ruleset->rules[$i]->compile($env);
                    $ruleset->rules = array_merge(
                        array_slice($ruleset->rules, 0, $i),
                        (array) $newRules,
                        array_slice($ruleset->rules, $i + 1)
                    );
                }
            }
        }

        // Store the frames around mixin definitions,
        // so they can be evaluated like closures when the time comes.
        foreach($ruleset->rules as $i => $rule) {
            if ($rule instanceof \Less\Node\Mixin\Definition) {
                $ruleset->rules[$i]->frames = $env->frames;
            }
        }

        // Evaluate mixin calls.
        for($i = 0; $i < count($ruleset->rules); $i++) {
            if ($ruleset->rules[$i] instanceof \Less\Node\Mixin\Call) {
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

        return $ruleset;
    }

    public function match($args)
    {
        return ! is_array($args) || count($args) === 0;
    }

    public function variables()
    {
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
    //     `context` holds an array of arrays.
    //
    public function toCSS ($context, $env)
    {
        $css = array();      // The CSS output
        $rules = array();    // node.Rule instances
        $rulesets = array(); // node.Ruleset instances
        $paths = array();    // Current selectors

        if (! $this->root) {
            if (count($context) === 0) {
                $paths = array_map(function ($s) { return array($s); }, $this->selectors);
            } else {
                $this->joinSelectors($paths, $context, $this->selectors);
            }
        }

        // Compile rules and rulesets
        foreach($this->rules as $rule) {
            if (isset($rule->rules) || ($rule instanceof \Less\Node\Directive)) {
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

                $selector = implode($env->compress ? ',' : (count($paths) > 3 ? ",\n" : ', '), $selector);

                $css[] = $selector;
                $css[] = ($env->compress ? '{' : " {\n  ") .
                         implode($env->compress ? '' : "\n  ", $rules) .
                         ($env->compress ? '}' : "\n}\n");
            }
        }
        $css[] = $rulesets;

        return implode('', $css) . ($env->compress ? "\n" : '');
    }

    public function joinSelectors (&$paths, $context, $selectors)
    {
        foreach($selectors as $selector) {
            $this->joinSelector($paths, $context, $selector);
        }
    }

    public function joinSelector (&$paths, $context, $selector)
    {
        $before = array();
        $after = array();
        $beforeElements = array();
        $afterElements = array();
        $hasParentSelector = false;

        foreach($selector->elements as $el) {
            if (strlen($el->combinator->value) > 0 && $el->combinator->value[0] === '&') {
                $hasParentSelector = true;
            }
            if ($hasParentSelector) {
                $afterElements[] = $el;
            } else {
                $beforeElements[] = $el;
            }
        }
        if (! $hasParentSelector) {
            $afterElements = $beforeElements;
            $beforeElements = array();
        }
        if (count($beforeElements) > 0) {
            $before[] = new \Less\Node\Selector($beforeElements);
        }
        if (count($afterElements) > 0) {
            $after[] = new \Less\Node\Selector($afterElements);
        }
        foreach($context as $c) {
            $paths[] = array_merge($before, $c, $after);
        }
    }
}
