<?php

namespace Less\Node;

class Import
{
	public $features;
	public $skip;
	public $full_path;
	public $path;

    /**
     * @param $path
     * @param string $imports
     * @param \Less\Environment|null $env
     */
    public function __construct($path, $full_path, $features = null, $skip ) {
        $this->_path = $path;
        $this->full_path = $full_path;
		$this->skip = $skip;
		$this->features = $features;

        // The '.less' extension is optional
        if ($path instanceof \Less\Node\Quoted) {
            $this->path = preg_match('/(\.[a-z]*$)|([\?;].*)$/', $path->value) ? $path->value : $path->value . '.less';
        } else {
            $this->path = isset($path->value->value) ? $path->value->value : $path->value;
        }

        $this->css = preg_match('/css([\?;].*)?$/', $full_path);
    }

    public function toCSS($env){

		$features = $this->features ? ' ' . $this->features->toCSS($env) : '';
        if( $this->css || !$this->full_path ){
            return "@import " . $this->_path->toCss() . $features . ";\n";
        } else {
            return "";
        }
    }

    public function compile($env) {

		$features = $this->features ? $this->features->compile($env) : null;

		// Only pre-compile .less files
        if ($this->css) {
            return new \Less\Node\Import( $this->_path, $this->full_path, $features, $this->skip );
		}

		if( !$this->full_path ){
			return $this;
		}

		if( $this->skip ){
			return array();
		}


		$parser = new \Less\Parser($env);
		$this->root = $parser->parseFile($this->full_path, true);

		$ruleset = new \Less\Node\Ruleset(array(), $this->root->rules );

		$ruleset->evalImports($env);

		if ($env->getDebug()) {
			array_unshift($ruleset->rules, new \Less\Node\Comment('/**** Start imported file `' . $this->path."` ****/\n", false));
			array_push($ruleset->rules,    new \Less\Node\Comment('/**** End imported file `' . $this->path."` ****/\n", false));
		}

		return $this->features ? new \Less\Node\Media($ruleset->rules, $this->features->value) : $ruleset->rules;

    }
}
