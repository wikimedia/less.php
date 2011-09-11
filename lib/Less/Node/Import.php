<?php

namespace Less\Node;

class Import
{
    public function __construct($path, $includeDir = '', $env = false)
    {
        $this->_path = $path;

        // The '.less' extension is optional
        if ($path instanceof \Less\Node\Quoted) {
            $this->path = preg_match('/\.(le?|c)ss(\?.*)?$/', $path->value) ? $path->value : $path->value . '.less';
        } else {
            $this->path = isset($path->value->value) ? $path->value->value : $path->value;
        }

        $this->css = preg_match('/css(\?.*)?$/', $this->path);

        // Only pre-compile .less files
        if ( ! $this->css) {

            $less = $includeDir . '/' . $this->path;
            $parser = new \Less\Parser($env);
            $this->root = $parser->parseFile($less, false);

        }
    }

    public function toCSS()
    {
        if ($this->css) {
            return "@import " . $this->_path->toCss() . ";\n";
        } else {
            return "";
        }
    }

    public function compile($env)
    {
        if ($this->css) {
            return $this;
        } else {
            $ruleset = new \Less\Node\Ruleset(null, isset($this->root->rules) ? $this->root->rules : array());
            for ($i = 0; $i < count($ruleset->rules); $i++) {
                if ($ruleset->rules[$i] instanceof \Less\Node\Import && ! $ruleset->rules[$i]->css) {

                    $newRules = $ruleset->rules[$i]->compile($env);
                    $ruleset->rules = array_merge(
                        array_slice($ruleset->rules, 0, $i),
                        (array) $newRules,
                        array_slice($ruleset->rules, $i + 1)
                    );
                }
            }
            return $ruleset->rules;
        }

    }
}

