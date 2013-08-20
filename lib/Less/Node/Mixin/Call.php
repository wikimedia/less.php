<?php

namespace Less\Node\Mixin;

class Call{

	public $type = 'MixinCall';
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
        $this->selector =  new \Less\Node\Selector($elements);
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

		$args = array_map(function ($a) use ($env) {
			return array('name'=> $a['name'], 'value' => $a['value']->compile($env) );
		}, $this->arguments);

        foreach($env->frames as $frame){

            if( $mixins = $frame->find($this->selector, null, $env) ){
				$isOneFound = true;
                foreach ($mixins as $mixin) {
                    $isRecursive = false;
                    foreach($env->frames as $recur_frame){
						if( !($mixin instanceof \Less\Node\Mixin\Definition) ){
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
						if( !method_exists($mixin,'matchCondition') || $mixin->matchCondition($args, $env) ){
							try {
								$rules = array_merge($rules, $mixin->compile($env, $args, $this->important)->rules);

							} catch (Exception $e) {
								throw new \Less\Exception\CompilerException($e->message, $e->index, null, $this->currentFileInfo['filename']);
							}
						}
						$match = true;
					}

                }

                if( $match ){
                    return $rules;
                }
            }
        }


        if( $isOneFound ){


			$message = '';
			if( $args ){
				$message = implode(', ',array_map(function($a) use($env){
					$argValue = '';
					if( $a['name'] ){
						$argValue += $a['name']+':';
					}
					if( $a['value'] && method_exists($a['value'],'toCSS') ){
						$argValue += $a['value']->toCSS();
					}else{
						$argValue += '???';
					}
					return $argValue;
				}, $args ));
			}

			throw new \Less\Exception\CompilerException('No matching definition was found for `'.
				trim($this->selector->toCSS($env)) . '(' .$message.')',
				$this->index, null, $this->currentFileInfo['filename']);

		}else{
			throw new \Less\Exception\CompilerException(trim($this->selector->toCSS($env)) . " is undefined", $this->index);
		}
    }
}
