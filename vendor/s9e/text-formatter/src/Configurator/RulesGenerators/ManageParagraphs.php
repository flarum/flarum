<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RulesGenerators;
use s9e\TextFormatter\Configurator\Helpers\TemplateForensics;
use s9e\TextFormatter\Configurator\RulesGenerators\Interfaces\BooleanRulesGenerator;
class ManageParagraphs implements BooleanRulesGenerator
{
	protected $p;
	public function __construct()
	{
		$this->p = new TemplateForensics('<p><xsl:apply-templates/></p>');
	}
	public function generateBooleanRules(TemplateForensics $src)
	{
		$rules = array();
		if ($src->allowsChild($this->p) && $src->isBlock() && !$this->p->closesParent($src))
			$rules['createParagraphs'] = \true;
		if ($src->closesParent($this->p))
			$rules['breakParagraph'] = \true;
		return $rules;
	}
}