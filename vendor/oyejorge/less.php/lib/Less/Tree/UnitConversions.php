<?php

/**
 * UnitConversions
 *
 * @package Less
 * @subpackage tree
 */
class Less_Tree_UnitConversions{

	public static $groups = array('length','duration','angle');

	public static $length = array(
		'm'=> 1,
		'cm'=> 0.01,
		'mm'=> 0.001,
		'in'=> 0.0254,
		'px'=> 0.000264583, // 0.0254 / 96,
		'pt'=> 0.000352778, // 0.0254 / 72,
		'pc'=> 0.004233333, // 0.0254 / 72 * 12
		);

	public static $duration = array(
		's'=> 1,
		'ms'=> 0.001
		);

	public static $angle = array(
		'rad' => 0.1591549430919,	// 1/(2*M_PI),
		'deg' => 0.002777778, 		// 1/360,
		'grad'=> 0.0025,			// 1/400,
		'turn'=> 1
		);

}