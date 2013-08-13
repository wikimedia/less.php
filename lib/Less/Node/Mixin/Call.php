<?php

namespace Less\Node\Mixin;

class Call{

	private $selector;
	private $arguments;
	private $index;
	private $filename;

	public $important;

	/**
	 * less.js: tree.mixin.Call
	 *
	 */
    public function __construct($elements, $args, $index, $filename, $important = false){

        $this->selector =  new \Less\Node\Selector($elements);
        $this->arguments = $args;
        $this->index = $index;
		$this->filename = $filename;
		$this->important = $important;
    }

	/**
	 * less.js: tree.mixin.Call.prototype()
	 *
	 */
    public function compile($env){

        $rules = array();
        $match = false;

        foreach($env->frames as $frame){

            if( $mixins = $frame->find($this->selector, null, $env) ){

                $args = array_map(function ($a) use ($env) {
					return array('name'=> $a['name'], 'value' => $a['value']->compile($env) );
                }, $this->arguments);

                foreach ($mixins as $mixin) {
                    if ($mixin->match($args, $env)) {
                        try {
                            $rules = array_merge($rules, $mixin->compile($env, $this->arguments, $this->important)->rules);
                            $match = true;
                        } catch (Exception $e) {
                            throw new \Less\Exception\CompilerException($e->message, $e->index, null, $this->filename);
                        }
                    }
                }

                if ($match) {
                    return $rules;
                } else {

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
						$this->index, null, $this->filename);
                }
            }
        }

        throw new \Less\Exception\CompilerException(trim($this->selector->toCSS($env)) . " is undefined", $this->index);
    }
}
