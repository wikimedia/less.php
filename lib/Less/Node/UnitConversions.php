<?php

namespace Less\Node;

class UnitConversions{

	static $groups = array('length','duration');

	static $length = array(
		'm'=> 1,
		'cm'=> 0.01,
		'mm'=> 0.001,
		'in'=> 0.0254,
		'pt'=> 0.000352778, // 0.0254 / 72,
		'pc'=> 0.004233333, //0.0254 / 72 * 12
		);

	static $duration = array(
		's'=> 1,
		'ms'=> 0.001
		);


}