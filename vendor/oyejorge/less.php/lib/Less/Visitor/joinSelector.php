<?php

/**
 * Join Selector Visitor
 *
 * @package Less
 * @subpackage visitor
 */
class Less_Visitor_joinSelector extends Less_Visitor{

	public $contexts = array( array() );

	/**
	 * @param Less_Tree_Ruleset $root
	 */
	public function run( $root ){
		return $this->visitObj($root);
	}

    public function visitRule( $ruleNode, &$visitDeeper ){
		$visitDeeper = false;
	}

    public function visitMixinDefinition( $mixinDefinitionNode, &$visitDeeper ){
		$visitDeeper = false;
	}

    public function visitRuleset( $rulesetNode ){

		$paths = array();

		if( !$rulesetNode->root ){
			$selectors = array();

			if( $rulesetNode->selectors && $rulesetNode->selectors ){
				foreach($rulesetNode->selectors as $selector){
					if( $selector->getIsOutput() ){
						$selectors[] = $selector;
					}
				}
			}

			if( !$selectors ){
				$rulesetNode->selectors = null;
				$rulesetNode->rules = null;
			}else{
				$context = end($this->contexts); //$context = $this->contexts[ count($this->contexts) - 1];
				$paths = $rulesetNode->joinSelectors( $context, $selectors);
			}

			$rulesetNode->paths = $paths;
		}

		$this->contexts[] = $paths; //different from less.js. Placed after joinSelectors() so that $this->contexts will get correct $paths
	}

    public function visitRulesetOut(){
		array_pop($this->contexts);
	}

    public function visitMedia($mediaNode) {
		$context = end($this->contexts); //$context = $this->contexts[ count($this->contexts) - 1];

		if( !count($context) || (is_object($context[0]) && $context[0]->multiMedia) ){
			$mediaNode->rules[0]->root = true;
		}
	}

}

