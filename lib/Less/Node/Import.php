<?php

namespace Less\Node;

class Import
{
    /**
     * @param $path
     * @param string $includeDir
     * @param \Less\Environment|null $env
     */
    public function __construct($path, $includeDir = '', $env = null)
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
            $this->root = $parser->parseFile($less, true);

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

            if ($env->getDebug()) {
                array_unshift($ruleset->rules, new \Less\Node\Comment('/**** Start imported file `' . $this->path."` ****/\n", false));
                array_push($ruleset->rules,    new \Less\Node\Comment('/**** End imported file `' . $this->path."` ****/\n", false));
            }

            return $ruleset->rules;
        }

    }
}

