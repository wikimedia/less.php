<?php


class Less_Tree_MixinCall extends Less_Tree{

	private $selector;
	private $arguments;
	private $index;
	private $currentFileInfo;

	public $important;

	/**
	 * less.js: tree.mixin.Call
	 *
	 */
	public function __construct($elements, $args, $index, $currentFileInfo, $important = false){
		$this->selector = new Less_Tree_Selector($elements);
		$this->arguments = $args;
		$this->index = $index;
		$this->currentFileInfo = $currentFileInfo;
		$this->important = $important;
	}

	function accept($visitor){
		$this->selector = $visitor->visit($this->selector);
		$this->arguments = $visitor->visit($this->arguments);
	}


	/**
	 * less.js: tree.mixin.Call.prototype()
	 *
	 */
	public function compile($env){

		$rules = array();
		$match = false;
		$isOneFound = false;

		$args = array();
		foreach($this->arguments as $a){
			$args[] = array('name'=> $a['name'], 'value' => $a['value']->compile($env) );
		}

		for($i = 0; $i< count($env->frames); $i++){

			$mixins = $env->frames[$i]->find($this->selector, null, $env);

			if( !$mixins ){
				continue;
			}

			$isOneFound = true;
			for( $m = 0; $m < count($mixins); $m++ ){
				$mixin = $mixins[$m];

				$isRecursive = false;
				foreach($env->frames as $recur_frame){
					if( !($mixin instanceof Less_Tree_MixinDefinition) ){
						if( (isset($recur_frame->originalRuleset) && $mixin === $recur_frame->originalRuleset) || ($mixin === $recur_frame) ){
							$isRecursive = true;
							break;
						}
					}
				}
				if( $isRecursive ){
					continue;
				}

				if ($mixin->matchArgs($args, $env)) {
					if( !Less_Parser::is_method($mixin,'matchCondition') || $mixin->matchCondition($args, $env) ){
						try{

							if( !($mixin instanceof Less_Tree_MixinDefinition) ){
								$mixin = new Less_Tree_MixinDefinition('', array(), $mixin->rules, null, false);
								if( $mixins[$m]->originalRuleset ){
									$mixin->originalRuleset = $mixins[$m]->originalRuleset;
								}else{
									$mixin->originalRuleset = $mixins[$m];
								}
							}
							//if (this.important) {
							//	isImportant = env.isImportant;
							//	env.isImportant = true;
							//}

							$rules = array_merge($rules, $mixin->compile($env, $args, $this->important)->rules);
							//if (this.important) {
							//	env.isImportant = isImportant;
							//}
						} catch (Exception $e) {
							//throw new Less_CompilerException($e->getMessage(), $e->index, null, $this->currentFileInfo['filename']);
							throw new Less_CompilerException($e->getMessage(), null, null, $this->currentFileInfo['filename']);
						}
					}
					$match = true;
				}

			}

			if( $match ){
				if( !$this->currentFileInfo || !$this->currentFileInfo['reference'] ){
					for( $i = 0; $i < count($rules); $i++ ){
						$rule = $rules[$i];
						if( Less_Parser::is_method($rule,'markReferenced') ){
							$rule->markReferenced();
						}
					}
				}
				return $rules;
			}
		}


		if( $isOneFound ){

			$message = array();
			if( $args ){
				foreach($args as $a){
					$argValue = '';
					if( $a['name'] ){
						$argValue += $a['name']+':';
					}
					if( Less_Parser::is_method($a['value'],'toCSS') ){
						$argValue += $a['value']->toCSS();
					}else{
						$argValue += '???';
					}
					$message[] = $argValue;
				}
			}
			$message = implode(', ',$message);


			throw new Less_CompilerException('No matching definition was found for `'.
				trim($this->selector->toCSS($env)) . '(' .$message.')',
				$this->index, null, $this->currentFileInfo['filename']);

		}else{
			throw new Less_CompilerException(trim($this->selector->toCSS($env)) . " is undefined", $this->index);
		}
	}
}


