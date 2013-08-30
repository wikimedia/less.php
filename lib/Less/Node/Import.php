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
	public $options;
	public $index;
	public $path;
	public $features;
	public $currentFileInfo;
	public $css;
	public $skip;
	public $root;

	function __construct($path, $features, $options, $index, $currentFileInfo = null ){
		$this->options = $options;
		$this->index = $index;
		$this->path = $path;
		$this->features = $features;
		$this->currentFileInfo = $currentFileInfo;


		if( isset($this->options['less']) ){
			$this->css = !$this->options['less'];
		} else {
			$pathValue = $this->getPath();
			if( $pathValue && preg_match('/css([\?;].*)?$/',$pathValue) ){
				$this->css = true;
			}
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
			return ( isset($this->css) || preg_match('/(\.[a-z]*$)|([\?;].*)$/',$path)) ? $path : $path . '.less';
		} else if ($this->path instanceof \Less\Node\URL) {
			return $this->path->value->value;
		}
		return null;
	}

	function compileForImport( $env ){
		return new \Less\Node\Import( $this->path->compile($env), $this->features, $this->options, $this->index, $this->currentFileInfo);
	}

	function compilePath($env) {
		$path = $this->path->compile($env);
		if( $this->currentFileInfo && $this->currentFileInfo['rootpath'] && !($path instanceof \Less\Node\URL)) {
			$pathValue = $path->value;
			// Add the base path if the import is relative
			if( $pathValue && $env->isPathRelative($pathValue) ){
				$path->value = $this->currentFileInfo['uri']. $pathValue;
			}
		}
		return $path;
	}

	function compile($env) {

		$evald = $this->compileForImport($env);
		$uri = '';

		//import once
		$evald_path = $evald->getPath();

		if( $evald_path && $env->isPathRelative($evald_path) ){
			$full_path = $evald->currentFileInfo['rootpath'].$evald_path;
			$uri = $this->currentFileInfo['uri'].dirname($evald_path);
		}else{
			$full_path = $evald_path;
		}

		$realpath = realpath($full_path);
		if( !isset($evald->options['multiple']) && $realpath && in_array($realpath,\Less\Parser::$imports) ){
			$evald->skip = true;
		}

		$features = ( $evald->features ? $evald->features->compile($env) : null );

		if ($evald->skip) { return array(); }

		if( $evald->css ){
			$temp = $this->compilePath( $env);
			return new \Less\Node\Import( $this->compilePath( $env), $features, $this->options, $this->index);
		}


		\Less\Parser::$imports[] = $realpath;
		$parser = new \Less\Parser($env);
		$evald->root = $parser->parseFile($full_path, $uri, true);
		$ruleset = new \Less\Node\Ruleset(array(), $evald->root->rules );
		$ruleset->evalImports($env);

		return $this->features ? new \Less\Node\Media($ruleset->rules, $this->features->value) : $ruleset->rules;
	}
}

