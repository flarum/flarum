<?php

/**
 * Ruleset
 *
 * @package Less
 * @subpackage tree
 */
class Less_Tree_Ruleset extends Less_Tree{

	protected $lookups;
	public $_variables;
	public $_rulesets;

	public $strictImports;

	public $selectors;
	public $rules;
	public $root;
	public $allowImports;
	public $paths;
	public $firstRoot;
	public $type = 'Ruleset';
	public $multiMedia;
	public $allExtends;

	public $ruleset_id;
	public $originalRuleset;

	public $first_oelements;

	public function SetRulesetIndex(){
		$this->ruleset_id = Less_Parser::$next_id++;
		$this->originalRuleset = $this->ruleset_id;

		if( $this->selectors ){
			foreach($this->selectors as $sel){
				if( $sel->_oelements ){
					$this->first_oelements[$sel->_oelements[0]] = true;
				}
			}
		}
	}

	public function __construct($selectors, $rules, $strictImports = null){
		$this->selectors = $selectors;
		$this->rules = $rules;
		$this->lookups = array();
		$this->strictImports = $strictImports;
		$this->SetRulesetIndex();
	}

	public function accept( $visitor ){
		if( $this->paths ){
			$paths_len = count($this->paths);
			for($i = 0,$paths_len; $i < $paths_len; $i++ ){
				$this->paths[$i] = $visitor->visitArray($this->paths[$i]);
			}
		}elseif( $this->selectors ){
			$this->selectors = $visitor->visitArray($this->selectors);
		}

		if( $this->rules ){
			$this->rules = $visitor->visitArray($this->rules);
		}
	}

	public function compile($env){

		$ruleset = $this->PrepareRuleset($env);


		// Store the frames around mixin definitions,
		// so they can be evaluated like closures when the time comes.
		$rsRuleCnt = count($ruleset->rules);
		for( $i = 0; $i < $rsRuleCnt; $i++ ){
			if( $ruleset->rules[$i] instanceof Less_Tree_Mixin_Definition || $ruleset->rules[$i] instanceof Less_Tree_DetachedRuleset ){
				$ruleset->rules[$i] = $ruleset->rules[$i]->compile($env);
			}
		}

		$mediaBlockCount = 0;
		if( $env instanceof Less_Environment ){
			$mediaBlockCount = count($env->mediaBlocks);
		}

		// Evaluate mixin calls.
		$this->EvalMixinCalls( $ruleset, $env, $rsRuleCnt );


		// Evaluate everything else
		for( $i=0; $i<$rsRuleCnt; $i++ ){
			if(! ($ruleset->rules[$i] instanceof Less_Tree_Mixin_Definition || $ruleset->rules[$i] instanceof Less_Tree_DetachedRuleset) ){
				$ruleset->rules[$i] = $ruleset->rules[$i]->compile($env);
			}
		}

        // Evaluate everything else
		for( $i=0; $i<$rsRuleCnt; $i++ ){
			$rule = $ruleset->rules[$i];

            // for rulesets, check if it is a css guard and can be removed
			if( $rule instanceof Less_Tree_Ruleset && $rule->selectors && count($rule->selectors) === 1 ){

                // check if it can be folded in (e.g. & where)
				if( $rule->selectors[0]->isJustParentSelector() ){
					array_splice($ruleset->rules,$i--,1);
					$rsRuleCnt--;

					for($j = 0; $j < count($rule->rules); $j++ ){
						$subRule = $rule->rules[$j];
						if( !($subRule instanceof Less_Tree_Rule) || !$subRule->variable ){
							array_splice($ruleset->rules, ++$i, 0, array($subRule));
							$rsRuleCnt++;
						}
					}

                }
            }
        }


		// Pop the stack
		$env->shiftFrame();

		if ($mediaBlockCount) {
			$len = count($env->mediaBlocks);
			for($i = $mediaBlockCount; $i < $len; $i++ ){
				$env->mediaBlocks[$i]->bubbleSelectors($ruleset->selectors);
			}
		}

		return $ruleset;
	}

	/**
	 * Compile Less_Tree_Mixin_Call objects
	 *
	 * @param Less_Tree_Ruleset $ruleset
	 * @param integer $rsRuleCnt
	 */
	private function EvalMixinCalls( $ruleset, $env, &$rsRuleCnt ){
		for($i=0; $i < $rsRuleCnt; $i++){
			$rule = $ruleset->rules[$i];

			if( $rule instanceof Less_Tree_Mixin_Call ){
				$rule = $rule->compile($env);

				$temp = array();
				foreach($rule as $r){
					if( ($r instanceof Less_Tree_Rule) && $r->variable ){
						// do not pollute the scope if the variable is
						// already there. consider returning false here
						// but we need a way to "return" variable from mixins
						if( !$ruleset->variable($r->name) ){
							$temp[] = $r;
						}
					}else{
						$temp[] = $r;
					}
				}
				$temp_count = count($temp)-1;
				array_splice($ruleset->rules, $i, 1, $temp);
				$rsRuleCnt += $temp_count;
				$i += $temp_count;
				$ruleset->resetCache();

			}elseif( $rule instanceof Less_Tree_RulesetCall ){

				$rule = $rule->compile($env);
				$rules = array();
				foreach($rule->rules as $r){
					if( ($r instanceof Less_Tree_Rule) && $r->variable ){
						continue;
					}
					$rules[] = $r;
				}

				array_splice($ruleset->rules, $i, 1, $rules);
				$temp_count = count($rules);
				$rsRuleCnt += $temp_count - 1;
				$i += $temp_count-1;
				$ruleset->resetCache();
			}

		}
	}


	/**
	 * Compile the selectors and create a new ruleset object for the compile() method
	 *
	 */
	private function PrepareRuleset($env){

		$hasOnePassingSelector = false;
		$selectors = array();
		if( $this->selectors ){
			Less_Tree_DefaultFunc::error("it is currently only allowed in parametric mixin guards,");

			foreach($this->selectors as $s){
				$selector = $s->compile($env);
				$selectors[] = $selector;
				if( $selector->evaldCondition ){
					$hasOnePassingSelector = true;
				}
			}

			Less_Tree_DefaultFunc::reset();
		} else {
			$hasOnePassingSelector = true;
		}

		if( $this->rules && $hasOnePassingSelector ){
			$rules = $this->rules;
		}else{
			$rules = array();
		}

		$ruleset = new Less_Tree_Ruleset($selectors, $rules, $this->strictImports);

		$ruleset->originalRuleset = $this->ruleset_id;

		$ruleset->root = $this->root;
		$ruleset->firstRoot = $this->firstRoot;
		$ruleset->allowImports = $this->allowImports;


		// push the current ruleset to the frames stack
		$env->unshiftFrame($ruleset);


		// Evaluate imports
		if( $ruleset->root || $ruleset->allowImports || !$ruleset->strictImports ){
			$ruleset->evalImports($env);
		}

		return $ruleset;
	}

	function evalImports($env) {

		$rules_len = count($this->rules);
		for($i=0; $i < $rules_len; $i++){
			$rule = $this->rules[$i];

			if( $rule instanceof Less_Tree_Import ){
				$rules = $rule->compile($env);
				if( is_array($rules) ){
					array_splice($this->rules, $i, 1, $rules);
					$temp_count = count($rules)-1;
					$i += $temp_count;
					$rules_len += $temp_count;
				}else{
					array_splice($this->rules, $i, 1, array($rules));
				}

				$this->resetCache();
			}
		}
	}

	function makeImportant(){

		$important_rules = array();
		foreach($this->rules as $rule){
			if( $rule instanceof Less_Tree_Rule || $rule instanceof Less_Tree_Ruleset || $rule instanceof Less_Tree_NameValue ){
				$important_rules[] = $rule->makeImportant();
			}else{
				$important_rules[] = $rule;
			}
		}

		return new Less_Tree_Ruleset($this->selectors, $important_rules, $this->strictImports );
	}

	public function matchArgs($args){
		return !$args;
	}

	// lets you call a css selector with a guard
	public function matchCondition( $args, $env ){
		$lastSelector = end($this->selectors);

		if( !$lastSelector->evaldCondition ){
			return false;
		}
		if( $lastSelector->condition && !$lastSelector->condition->compile( $env->copyEvalEnv( $env->frames ) ) ){
			return false;
		}
		return true;
	}

	function resetCache(){
		$this->_rulesets = null;
		$this->_variables = null;
		$this->lookups = array();
	}

	public function variables(){
		$this->_variables = array();
		foreach( $this->rules as $r){
			if ($r instanceof Less_Tree_Rule && $r->variable === true) {
				$this->_variables[$r->name] = $r;
			}
		}
	}

	public function variable($name){

		if( is_null($this->_variables) ){
			$this->variables();
		}
		return isset($this->_variables[$name]) ? $this->_variables[$name] : null;
	}

	public function find( $selector, $self = null ){

		$key = implode(' ',$selector->_oelements);

		if( !isset($this->lookups[$key]) ){

			if( !$self ){
				$self = $this->ruleset_id;
			}

			$this->lookups[$key] = array();

			$first_oelement = $selector->_oelements[0];

			foreach($this->rules as $rule){
				if( $rule instanceof Less_Tree_Ruleset && $rule->ruleset_id != $self ){

					if( isset($rule->first_oelements[$first_oelement]) ){

						foreach( $rule->selectors as $ruleSelector ){
							$match = $selector->match($ruleSelector);
							if( $match ){
								if( $selector->elements_len > $match ){
									$this->lookups[$key] = array_merge($this->lookups[$key], $rule->find( new Less_Tree_Selector(array_slice($selector->elements, $match)), $self));
								} else {
									$this->lookups[$key][] = $rule;
								}
								break;
							}
						}
					}
				}
			}
		}

		return $this->lookups[$key];
	}


	/**
	 * @see Less_Tree::genCSS
	 */
	public function genCSS( $output ){

		if( !$this->root ){
			Less_Environment::$tabLevel++;
		}

		$tabRuleStr = $tabSetStr = '';
		if( !Less_Parser::$options['compress'] ){
			if( Less_Environment::$tabLevel ){
				$tabRuleStr = "\n".str_repeat( Less_Parser::$options['indentation'] , Less_Environment::$tabLevel );
				$tabSetStr = "\n".str_repeat( Less_Parser::$options['indentation'] , Less_Environment::$tabLevel-1 );
			}else{
				$tabSetStr = $tabRuleStr = "\n";
			}
		}


		$ruleNodes = array();
		$rulesetNodes = array();
		foreach($this->rules as $rule){

			$class = get_class($rule);
			if( ($class === 'Less_Tree_Media') || ($class === 'Less_Tree_Directive') || ($this->root && $class === 'Less_Tree_Comment') || ($class === 'Less_Tree_Ruleset' && $rule->rules) ){
				$rulesetNodes[] = $rule;
			}else{
				$ruleNodes[] = $rule;
			}
		}

		// If this is the root node, we don't render
		// a selector, or {}.
		if( !$this->root ){

			/*
			debugInfo = tree.debugInfo(env, this, tabSetStr);

			if (debugInfo) {
				output.add(debugInfo);
				output.add(tabSetStr);
			}
			*/

			$paths_len = count($this->paths);
			for( $i = 0; $i < $paths_len; $i++ ){
				$path = $this->paths[$i];
				$firstSelector = true;

				foreach($path as $p){
					$p->genCSS( $output, $firstSelector );
					$firstSelector = false;
				}

				if( $i + 1 < $paths_len ){
					$output->add( ',' . $tabSetStr );
				}
			}

			$output->add( (Less_Parser::$options['compress'] ? '{' : " {") . $tabRuleStr );
		}

		// Compile rules and rulesets
		$ruleNodes_len = count($ruleNodes);
		$rulesetNodes_len = count($rulesetNodes);
		for( $i = 0; $i < $ruleNodes_len; $i++ ){
			$rule = $ruleNodes[$i];

			// @page{ directive ends up with root elements inside it, a mix of rules and rulesets
			// In this instance we do not know whether it is the last property
			if( $i + 1 === $ruleNodes_len && (!$this->root || $rulesetNodes_len === 0 || $this->firstRoot ) ){
				Less_Environment::$lastRule = true;
			}

			$rule->genCSS( $output );

			if( !Less_Environment::$lastRule ){
				$output->add( $tabRuleStr );
			}else{
				Less_Environment::$lastRule = false;
			}
		}

		if( !$this->root ){
			$output->add( $tabSetStr . '}' );
			Less_Environment::$tabLevel--;
		}

		$firstRuleset = true;
		$space = ($this->root ? $tabRuleStr : $tabSetStr);
		for( $i = 0; $i < $rulesetNodes_len; $i++ ){

			if( $ruleNodes_len && $firstRuleset ){
				$output->add( $space );
			}elseif( !$firstRuleset ){
				$output->add( $space );
			}
			$firstRuleset = false;
			$rulesetNodes[$i]->genCSS( $output);
		}

		if( !Less_Parser::$options['compress'] && $this->firstRoot ){
			$output->add( "\n" );
		}

	}


	function markReferenced(){
		if( !$this->selectors ){
			return;
		}
		foreach($this->selectors as $selector){
			$selector->markReferenced();
		}
	}

	public function joinSelectors( $context, $selectors ){
		$paths = array();
		if( is_array($selectors) ){
			foreach($selectors as $selector) {
				$this->joinSelector( $paths, $context, $selector);
			}
		}
		return $paths;
	}

	public function joinSelector( &$paths, $context, $selector){

		$hasParentSelector = false;

		foreach($selector->elements as $el) {
			if( $el->value === '&') {
				$hasParentSelector = true;
			}
		}

		if( !$hasParentSelector ){
			if( $context ){
				foreach($context as $context_el){
					$paths[] = array_merge($context_el, array($selector) );
				}
			}else {
				$paths[] = array($selector);
			}
			return;
		}


		// The paths are [[Selector]]
		// The first list is a list of comma separated selectors
		// The inner list is a list of inheritance separated selectors
		// e.g.
		// .a, .b {
		//   .c {
		//   }
		// }
		// == [[.a] [.c]] [[.b] [.c]]
		//

		// the elements from the current selector so far
		$currentElements = array();
		// the current list of new selectors to add to the path.
		// We will build it up. We initiate it with one empty selector as we "multiply" the new selectors
		// by the parents
		$newSelectors = array(array());


		foreach( $selector->elements as $el){

			// non parent reference elements just get added
			if( $el->value !== '&' ){
				$currentElements[] = $el;
			} else {
				// the new list of selectors to add
				$selectorsMultiplied = array();

				// merge the current list of non parent selector elements
				// on to the current list of selectors to add
				if( $currentElements ){
					$this->mergeElementsOnToSelectors( $currentElements, $newSelectors);
				}

				// loop through our current selectors
				foreach($newSelectors as $sel){

					// if we don't have any parent paths, the & might be in a mixin so that it can be used
					// whether there are parents or not
					if( !$context ){
						// the combinator used on el should now be applied to the next element instead so that
						// it is not lost
						if( $sel ){
							$sel[0]->elements = array_slice($sel[0]->elements,0);
							$sel[0]->elements[] = new Less_Tree_Element($el->combinator, '', $el->index, $el->currentFileInfo );
						}
						$selectorsMultiplied[] = $sel;
					}else {

						// and the parent selectors
						foreach($context as $parentSel){
							// We need to put the current selectors
							// then join the last selector's elements on to the parents selectors

							// our new selector path
							$newSelectorPath = array();
							// selectors from the parent after the join
							$afterParentJoin = array();
							$newJoinedSelectorEmpty = true;

							//construct the joined selector - if & is the first thing this will be empty,
							// if not newJoinedSelector will be the last set of elements in the selector
							if( $sel ){
								$newSelectorPath = $sel;
								$lastSelector = array_pop($newSelectorPath);
								$newJoinedSelector = $selector->createDerived( array_slice($lastSelector->elements,0) );
								$newJoinedSelectorEmpty = false;
							}
							else {
								$newJoinedSelector = $selector->createDerived(array());
							}

							//put together the parent selectors after the join
							if ( count($parentSel) > 1) {
								$afterParentJoin = array_merge($afterParentJoin, array_slice($parentSel,1) );
							}

							if ( $parentSel ){
								$newJoinedSelectorEmpty = false;

								// join the elements so far with the first part of the parent
								$newJoinedSelector->elements[] = new Less_Tree_Element( $el->combinator, $parentSel[0]->elements[0]->value, $el->index, $el->currentFileInfo);

								$newJoinedSelector->elements = array_merge( $newJoinedSelector->elements, array_slice($parentSel[0]->elements, 1) );
							}

							if (!$newJoinedSelectorEmpty) {
								// now add the joined selector
								$newSelectorPath[] = $newJoinedSelector;
							}

							// and the rest of the parent
							$newSelectorPath = array_merge($newSelectorPath, $afterParentJoin);

							// add that to our new set of selectors
							$selectorsMultiplied[] = $newSelectorPath;
						}
					}
				}

				// our new selectors has been multiplied, so reset the state
				$newSelectors = $selectorsMultiplied;
				$currentElements = array();
			}
		}

		// if we have any elements left over (e.g. .a& .b == .b)
		// add them on to all the current selectors
		if( $currentElements ){
			$this->mergeElementsOnToSelectors($currentElements, $newSelectors);
		}
		foreach( $newSelectors as $new_sel){
			if( $new_sel ){
				$paths[] = $new_sel;
			}
		}
	}

	function mergeElementsOnToSelectors( $elements, &$selectors){

		if( !$selectors ){
			$selectors[] = array( new Less_Tree_Selector($elements) );
			return;
		}


		foreach( $selectors as &$sel){

			// if the previous thing in sel is a parent this needs to join on to it
			if( $sel ){
				$last = count($sel)-1;
				$sel[$last] = $sel[$last]->createDerived( array_merge($sel[$last]->elements, $elements) );
			}else{
				$sel[] = new Less_Tree_Selector( $elements );
			}
		}
	}
}
