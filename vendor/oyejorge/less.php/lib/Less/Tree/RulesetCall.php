<?php

/**
 * RulesetCall
 *
 * @package Less
 * @subpackage tree
 */
class Less_Tree_RulesetCall extends Less_Tree{

	public $variable;
	public $type = "RulesetCall";

    public function __construct($variable){
		$this->variable = $variable;
	}

    public function accept($visitor) {}

    public function compile( $env ){
		$variable = new Less_Tree_Variable($this->variable);
		$detachedRuleset = $variable->compile($env);
		return $detachedRuleset->callEval($env);
	}
}

