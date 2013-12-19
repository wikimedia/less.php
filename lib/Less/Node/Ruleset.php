<?php


class Less_Tree_Ruleset extends Less_Tree{

	protected $lookups;
	public $_variables;
	public $_rulesets;

	public $strictImports;

	public $selectors;
	public $rules;
	public $root;
	public $allowImports;
	public $paths;
	public $firstRoot;
	public $type = 'Ruleset';


	var $ruleset_id;
	var $originalRuleset;


	public function SetRulesetIndex(){
		static $i = 0;
		$this->ruleset_id = $i++;
		$this->originalRuleset = $this->ruleset_id;
	}

	public function __construct($selectors, $rules, $strictImports = null){
		$this->selectors = $selectors;
		$this->rules = $rules;
		$this->lookups = array();
		$this->strictImports = $strictImports;
		$this->SetRulesetIndex();
	}

	function accept( $visitor ){
		if( $this->paths ){
			$paths_len = count($this->paths);
			for($i = 0,$paths_len; $i < $paths_len; $i++ ){
				$this->paths[$i] = $visitor->visit($this->paths[$i]);
			}
		}else{
			$this->selectors = $visitor->visit($this->selectors);
		}
		$this->rules = $visitor->visit($this->rules);
	}

	public function compile($env){

		$selectors = array();
		if( $this->selectors ){
			foreach($this->selectors as $s){
				if( Less_Parser::is_method($s,'compile') ){
					$selectors[] = $s->compile($env);
				}
			}
		}
		$ruleset = new Less_Tree_Ruleset($selectors, $this->rules, $this->strictImports);
		$rules = array();

		$ruleset->originalRuleset = $this->ruleset_id;

		$ruleset->root = $this->root;
		$ruleset->firstRoot = $this->firstRoot;
		$ruleset->allowImports = $this->allowImports;

		// push the current ruleset to the frames stack
		$env->unshiftFrame($ruleset);

		// currrent selectors
		array_unshift($env->selectors,$this->selectors);


		// Evaluate imports
		if ($ruleset->root || $ruleset->allowImports || !$ruleset->strictImports) {
			$ruleset->evalImports($env);
		}


		// Store the frames around mixin definitions,
		// so they can be evaluated like closures when the time comes.
		$ruleset_len = count($ruleset->rules);
		for( $i = 0; $i < $ruleset_len; $i++ ){
			if( $ruleset->rules[$i] instanceof Less_Tree_MixinDefinition ){
				$ruleset->rules[$i]->frames = array_slice($env->frames,0);;
			}
		}

		$mediaBlockCount = 0;
		if( $env instanceof Less_Environment ){
			$mediaBlockCount = count($env->mediaBlocks);
		}

		// Evaluate mixin calls.
		for($i=0; $i < $ruleset_len; $i++){
			$rule = $ruleset->rules[$i];
			if( $rule instanceof Less_Tree_MixinCall ){
				$rules = $rule->compile($env);

				$temp = array();
				foreach($rules as $r){
					if( ($r instanceof Less_Tree_Rule) && $r->variable ){
						// do not pollute the scope if the variable is
						// already there. consider returning false here
						// but we need a way to "return" variable from mixins
						if( !$ruleset->variable($r->name) ){
							$temp[] = $r;
						}
					}else{
						$temp[] = $r;
					}
				}
				$rules = $temp;
				array_splice($ruleset->rules, $i, 1, $rules);
				$ruleset_len = count($ruleset->rules);
				$i += count($rules)-1;
				$ruleset->resetCache();
			}
		}


		for( $i=0; $i<$ruleset_len; $i++ ){
			if(! ($ruleset->rules[$i] instanceof Less_Tree_MixinDefinition) ){
				$ruleset->rules[$i] = Less_Parser::is_method($ruleset->rules[$i],'compile') ? $ruleset->rules[$i]->compile($env) : $ruleset->rules[$i];
			}
		}


		// Pop the stack
		$env->shiftFrame();
		array_shift($env->selectors);

		if ($mediaBlockCount) {
			for($i = $mediaBlockCount; $i < count($env->mediaBlocks); $i++ ){
				$env->mediaBlocks[$i]->bubbleSelectors($selectors);
			}
		}

		return $ruleset;
	}

	function evalImports($env) {

		$rules_len = count($this->rules);
		for($i=0; $i < $rules_len; $i++){
			$rule = $this->rules[$i];

			if( $rule instanceof Less_Tree_Import ){
				$rules = $rule->compile($env);
				if( is_array($rules) ){
					array_splice($this->rules, $i, 1, $rules);
					$i += count($rules)-1;
					$rules_len = count($this->rules);
				}else{
					array_splice($this->rules, $i, 1, array($rules));
				}

				$this->resetCache();
			}
		}
	}

	function makeImportant(){

		$important_rules = array();
		foreach($this->rules as $rule){
			if( Less_Parser::is_method($rule,'makeImportant') ){
				$important_rules[] = $rule->makeImportant();
			}else{
				$important_rules[] = $rule;
			}
		}

		return new Less_Tree_Ruleset($this->selectors, $important_rules, $this->strictImports );
	}

	public function matchArgs($args){
		return !is_array($args) || count($args) === 0;
	}

	public function matchCondition( $args, $env ){
		$lastSelector = end($this->selectors);
		if( $lastSelector->condition && !$lastSelector->condition->compile( $env->copyEvalEnv( $env->frames ) ) ){
			return false;
		}
		return true;
	}

	function resetCache() {
		$this->_rulesets = null;
		$this->_variables = null;
		$this->lookups = array();
	}

	public function variables(){

		if( !$this->_variables ){
			$this->_variables = array();
			foreach( $this->rules as $r){
				if ($r instanceof Less_Tree_Rule && $r->variable === true) {
					$this->_variables[$r->name] = $r;
				}
			}
		}

		return $this->_variables;
	}

	public function variable($name){
		$vars = $this->variables();
		return isset($vars[$name]) ? $vars[$name] : null;
	}

	public function find( $selector, $self = null, $env = null){

		if( !$self ){
			$self = $this;
		}

		$key = $selector->toCSS($env);

		if( !array_key_exists($key, $this->lookups) ){
			$this->lookups[$key] = array();;


			foreach($this->rules as $rule){

				if( $rule == $self ){
					continue;
				}

				if( ($rule instanceof Less_Tree_Ruleset) || ($rule instanceof Less_Tree_MixinDefinition) ){

					foreach( $rule->selectors as $ruleSelector ){
						$match = $selector->match($ruleSelector);
						if( $match ){
							if( count($selector->elements) > $match ){
								$this->lookups[$key] = array_merge($this->lookups[$key], $rule->find( new Less_Tree_Selector(array_slice($selector->elements, $match)), $self, $env));
							} else {
								$this->lookups[$key][] = $rule;
							}
							break;
						}
					}
				}
			}
		}

		return $this->lookups[$key];
	}

	public function genCSS( $env, &$strs ){
		$ruleNodes = array();
		$rulesetNodes = array();
		$firstRuleset = true;

		if( !$this->root ){
			$env->tabLevel++;
		}

		$tabRuleStr = $tabSetStr = '';
		if( !Less_Environment::$compress && $env->tabLevel ){
			$tabRuleStr = str_repeat( '  ' , $env->tabLevel );
			$tabSetStr = str_repeat( '  ' , $env->tabLevel-1 );
		}

		foreach($this->rules as $rule){
			if( ( is_object($rule) && property_exists($rule,'rules') && $rule->rules) || ($rule instanceof Less_Tree_Media) || $rule instanceof Less_Tree_Directive || ($this->root && $rule instanceof Less_Tree_Comment) ){
				$rulesetNodes[] = $rule;
			} else {
				$ruleNodes[] = $rule;
			}
		}

		// If this is the root node, we don't render
		// a selector, or {}.
		if( !$this->root ){

			/*
			debugInfo = tree.debugInfo(env, this, tabSetStr);

			if (debugInfo) {
				output.add(debugInfo);
				output.add(tabSetStr);
			}
			*/

			for( $i = 0,$paths_len = count($this->paths); $i < $paths_len; $i++ ){
				$path = $this->paths[$i];
				Less_Environment::$firstSelector = true;
				$path_len = count($path);
				for($j = 0; $j < $path_len; $j++ ){
					$path[$j]->genCSS($env, $strs );
					Less_Environment::$firstSelector = false;
				}
				if( $i + 1 < $paths_len ){
					self::OutputAdd( $strs, Less_Environment::$compress ? ',' : (",\n" . $tabSetStr) );
				}
			}

			self::OutputAdd( $strs, (Less_Environment::$compress ? '{' : " {\n") . $tabRuleStr );
		}

		// Compile rules and rulesets
		$ruleNodes_len = count($ruleNodes);
		$rulesetNodes_len = count($rulesetNodes);
		for( $i = 0; $i < $ruleNodes_len; $i++ ){
			$rule = $ruleNodes[$i];

			// @page{ directive ends up with root elements inside it, a mix of rules and rulesets
			// In this instance we do not know whether it is the last property
			if( $i + 1 === $ruleNodes_len && (!$this->root || $rulesetNodes_len === 0 || $this->firstRoot ) ){
				$env->lastRule = true;
			}

			if( Less_Parser::is_method($rule,'genCSS') ){
				$rule->genCSS( $env, $strs );
			}elseif( is_object($rule) && property_exists($rule,'value') && $rule->value ){
				self::OutputAdd( $strs, (string)$rule->value );
			}

			if( !property_exists($env,'lastRule') || !$env->lastRule ){
				self::OutputAdd( $strs, Less_Environment::$compress ? '' : ("\n" . $tabRuleStr) );
			}else{
				$env->lastRule = false;
			}
		}

		if( !$this->root ){
			self::OutputAdd( $strs, (Less_Environment::$compress ? '}' : "\n" . $tabSetStr . '}'));
			$env->tabLevel--;
		}

		for( $i = 0; $i < $rulesetNodes_len; $i++ ){
			if( $ruleNodes_len && $firstRuleset ){
				self::OutputAdd( $strs, (Less_Environment::$compress ? "" : "\n") . ($this->root ? $tabRuleStr : $tabSetStr) );
			}
			if( !$firstRuleset ){
				self::OutputAdd( $strs, (Less_Environment::$compress ? "" : "\n") . ($this->root ? $tabRuleStr : $tabSetStr));
			}
			$firstRuleset = false;
			$rulesetNodes[$i]->genCSS($env, $strs);
		}

		if( !count($strs) && !Less_Environment::$compress && $this->firstRoot ){
			self::OutputAdd( $strs, "\n" );
		}

	}

	function markReferenced(){

		foreach($this->selectors as $selector){
			$selector->markReferenced();
		}
	}

	public function joinSelectors( $context, $selectors ){
		$paths = array();
		if( is_array($selectors) ){
			foreach($selectors as $selector) {
				$this->joinSelector( $paths, $context, $selector);
			}
		}
		return $paths;
	}

	public function joinSelector( &$paths, $context, $selector){

		$hasParentSelector = false;

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
					if( !count($context) ){
						// the combinator used on el should now be applied to the next element instead so that
						// it is not lost
						if( count($sel) > 0 ){
							$sel[0]->elements = array_slice($sel[0]->elements,0);
							$sel[0]->elements[] = new Less_Tree_Element($el->combinator, '', 0, $el->index, $el->currentFileInfo );
						}
						$selectorsMultiplied[] = $sel;
					}else {

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
								$newSelectorPath = $sel;
								$lastSelector = array_pop($newSelectorPath);
								$newJoinedSelector = $selector->createDerived( array_slice($lastSelector->elements,0) );
								$newJoinedSelectorEmpty = false;
							}
							else {
								$newJoinedSelector = $selector->createDerived(array());
							}

							//put together the parent selectors after the join
							if ( count($parentSel) > 1) {
								$afterParentJoin = array_merge($afterParentJoin, array_slice($parentSel,1) );
							}

							if ( count($parentSel) > 0) {
								$newJoinedSelectorEmpty = false;

								// join the elements so far with the first part of the parent
								$newJoinedSelector->elements[] = new Less_Tree_Element( $el->combinator, $parentSel[0]->elements[0]->value, 0, $el->index, $el->currentFileInfo);

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
			if( count($new_sel) ){
				$paths[] = $new_sel;
			}
		}
	}

	function mergeElementsOnToSelectors( $elements, &$selectors){

		if( count($selectors) == 0) {
			$selectors[] = array( new Less_Tree_Selector($elements) );
			return;
		}


		foreach( $selectors as &$sel){

			// if the previous thing in sel is a parent this needs to join on to it
			if ( count($sel) > 0) {
				$last = count($sel)-1;
				$sel[$last] = $sel[$last]->createDerived( array_merge($sel[$last]->elements, $elements) );
			}else{
				$sel[] = new Less_Tree_Selector( $elements );
			}
		}
	}
}
