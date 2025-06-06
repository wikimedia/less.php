<?php
/**
 * @private
 */
class Less_Tree_UnitConversions {

	/** @var string[] */
	public static $groups = [ 'length', 'duration', 'angle' ];

	/** @var array<string,float> */
	public static $length = [
		'm' => 1,
		'cm' => 0.01,
		'mm' => 0.001,
		'in' => 0.0254,
		'px' => 0.00026458333333, // 0.0254 / 96,
		'pt' => 0.00035277777777777776, // 0.0254 / 72,
		'pc' => 0.004233333333333333, // 0.0254 / 72 * 12
	];

	/** @var array<string,float> */
	public static $duration = [
		's' => 1,
		'ms' => 0.001
	];

	/** @var array<string,float> */
	public static $angle = [
		'rad' => 0.1591549430919, // 1/(2*M_PI),
		'deg' => 0.002777778, // 1/360,
		'grad' => 0.0025, // 1/400,
		'turn' => 1
	];

}
