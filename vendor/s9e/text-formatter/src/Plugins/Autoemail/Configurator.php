<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\Autoemail;
use s9e\TextFormatter\Plugins\ConfiguratorBase;
class Configurator extends ConfiguratorBase
{
	protected $attrName = 'email';
	protected $quickMatch = '@';
	protected $regexp = '/\\b[-a-z0-9_+.]+@[-a-z0-9.]*[a-z0-9]/Si';
	protected $tagName = 'EMAIL';
	protected function setUp()
	{
		if (isset($this->configurator->tags[$this->tagName]))
			return;
		$tag = $this->configurator->tags->add($this->tagName);
		$filter = $this->configurator->attributeFilters->get('#email');
		$tag->attributes->add($this->attrName)->filterChain->append($filter);
		$tag->template = '<a href="mailto:{@' . $this->attrName . '}"><xsl:apply-templates/></a>';
	}
}