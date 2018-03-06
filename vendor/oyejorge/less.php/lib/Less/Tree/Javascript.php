<?php

/**
 * Javascript
 *
 * @package Less
 * @subpackage tree
 */
class Less_Tree_Javascript extends Less_Tree{

	public $type = 'Javascript';
	public $escaped;
	public $expression;
	public $index;

	/**
	 * @param boolean $index
	 * @param boolean $escaped
	 */
	public function __construct($string, $index, $escaped){
		$this->escaped = $escaped;
		$this->expression = $string;
		$this->index = $index;
	}

	public function compile(){
		return new Less_Tree_Anonymous('/* Sorry, can not do JavaScript evaluation in PHP... :( */');
	}

}
