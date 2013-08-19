<?php

namespace Less\Node;


//
// CSS @import node
//
// The general strategy here is that we don't want to wait
// for the parsing to be completed, before we start importing
// the file. That's because in the context of a browser,
// most of the time will be spent waiting for the server to respond.
//
// On creation, we push the import path to our import queue, though
// `import,push`, we also pass it a callback, which it'll call once
// the file has been fetched, and parsed.
//
class Import{

	public $type = 'Import';
	public $once;
	public $index;
	public $path;
	public $features;
	public $rootpath;
	public $css;
	public $skip;

	function __construct($path, $features, $once, $index, $rootpath = null ){
		$this->once = $once;
		$this->index = $index;
		$this->path = $path;
		$this->features = $features;
		$this->rootpath = $rootpath;

		$pathValue = $this->getPath();
		if( $pathValue ){
			$this->css = preg_match('/css([\?;].*)?$/',$pathValue);
		}
    }

//
// The actual import node doesn't return anything, when converted to CSS.
// The reason is that it's used at the evaluation stage, so that the rules
// it imports can be treated like any other rules.
//
// In `eval`, we make sure all Import nodes get evaluated, recursively, so
// we end up with a flat structure, which can easily be imported in the parent
// ruleset.
//

	function accept($visitor) {
		$this->features = $visitor->visit($this->features);
		$this->path = $visitor->visit($this->path);
		$this->root = $visitor->visit($this->root);
	}

	function toCSS($env) {
		$features = $this->features ? ' ' . $this->features->toCSS($env) : '';

		if ($this->css) {
			return "@import " . $this->path->toCSS() . $features . ";\n";
		} else {
			return "";
		}
	}

	function getPath(){
		if ($this->path instanceof \Less\Node\Quoted) {
			$path = $this->path->value;
			return ($this->css || preg_match('/(\.[a-z]*$)|([\?;].*)$/',$path)) ? $path : $path . '.less';
		} else if ($this->path instanceof \Less\Node\URL) {
			return $this->path->value->value;
		}
		return null;
	}

	function compileForImport( $env ){
		return new \Less\Node\Import( $this->path->compile($env), $this->features, $this->once, $this->index);
	}

	function compilePath($env) {
		$path = $this->path->compile($env);
		if ($this->rootpath && !($path instanceof \Less\Node\URL)) {
			$pathValue = $path->value;
			// Add the base path if the import is relative
			if( $pathValue && !preg_match('/^(?:[a-z\-]+:|\/)/', $pathValue) ){
				$path->value = $this->rootpath . $pathValue;
			}
		}
		return $path;
	}

	function compile($env) {

		$features = ( $this->features ? $this->features->compile($env) : null );

		if ($this->skip) { return []; }

		if ($this->css) {
			$temp = new \Less\Node\Import( $this->compilePath( $env), $features, $this->once, $this->index);
			return $temp;
		}


		$full_path = $this->rootpath.$this->getPath();
		$parser = new \Less\Parser($env);
		$this->root = $parser->parseFile($full_path, true);
		$ruleset = new \Less\Node\Ruleset(array(), $this->root->rules );
		$ruleset->evalImports($env);

		return $this->features ? new \Less\Node\Media($ruleset->rules, $this->features->value) : $ruleset->rules;
	}
}

