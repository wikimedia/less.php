<?php

class Less_Tree{

	public function toCSS($env){
		$strs = array();
		$this->genCSS($env, $strs );
		return implode('',$strs);
	}

	public function toCSS_Add( &$strs, $chunk, $fileInfo = null, $index = null ){
		$strs[] = $chunk;
	}


	public function outputRuleset($env, $output, $rules ){
		$output->add( ($env->compress ? '{' : " {\n") );

		/*
		output.add((env.compress ? '{' : ' {\n'));
		env.tabLevel = (env.tabLevel || 0) + 1;
		var tabRuleStr = env.compress ? '' : Array(env.tabLevel + 1).join("  "),
			tabSetStr = env.compress ? '' : Array(env.tabLevel).join("  ");
		for(var i = 0; i < rules.length; i++) {
			output.add(tabRuleStr);
			rules[i].genCSS(env, output);
			output.add(env.compress ? '' : '\n');
		}
		env.tabLevel--;
		output.add(tabSetStr + "}");
		*/
	}

}