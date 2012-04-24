<?php

namespace Less\Node;

class Import
{
	public $importDir;
	public $features;
	public $once;

    /**
     * @param $path
     * @param string $imports
     * @param \Less\Environment|null $env
     */
    public function __construct($path, $importDir = '', $features = null, $once = null, $env = null) {
        $this->_path = $path;
		$this->importDir = $importDir;
		$this->once = $once;
		$this->features = $features ? new \Less\Node\Value($features) : null;

        // The '.less' extension is optional
        if ($path instanceof \Less\Node\Quoted) {
            $this->path = preg_match('/\.(le?|c)ss(\?.*)?$/', $path->value) ? $path->value : $path->value . '.less';
        } else {
            $this->path = isset($path->value->value) ? $path->value->value : $path->value;
        }

        $this->css = preg_match('/css(\?.*)?$/', $this->path);
    }

    public function toCSS($env)
    {
		$features = $this->features ? ' ' . $this->features->toCSS($env) : '';
        if ($this->css) {
            return "@import " . $this->_path->toCss() . $features . ";\n";
        } else {
            return "";
        }
    }

    public function compile($env) {
		$features = $this->features ? $this->features->compile($env) : null;
        if ($this->css) {
            return $this;
        } else {
			// Only pre-compile .less files
			if ( ! $this->css) {
				$less = $this->importDir . '/' . $this->path;
				$parser = new \Less\Parser($env);
				$this->root = $parser->parseFile($less, true);
			}

            $ruleset = new \Less\Node\Ruleset(array(), isset($this->root->rules) ? $this->root->rules : array());
            for ($i = 0; $i < count($ruleset->rules); $i++) {
                if ($ruleset->rules[$i] instanceof \Less\Node\Import && ! $ruleset->rules[$i]->css) {
                    $newRules = $ruleset->rules[$i]->compile($env);
                    $ruleset->rules = array_merge(
                        array_slice($ruleset->rules, 0, $i),
                        is_array($newRules) ? $newRules : array($newRules),
                        array_slice($ruleset->rules, $i + 1)
                    );
                }
            }

            if ($env->getDebug()) {
                array_unshift($ruleset->rules, new \Less\Node\Comment('/**** Start imported file `' . $this->path."` ****/\n", false));
                array_push($ruleset->rules,    new \Less\Node\Comment('/**** End imported file `' . $this->path."` ****/\n", false));
            }

            return $this->features ? new Media($ruleset->rules, $this->features->value) : $ruleset->rules;
        }

    }
}
