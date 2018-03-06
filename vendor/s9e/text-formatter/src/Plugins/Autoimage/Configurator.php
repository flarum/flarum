<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\Autoimage;
use s9e\TextFormatter\Plugins\ConfiguratorBase;
class Configurator extends ConfiguratorBase
{
	protected $attrName = 'src';
	protected $quickMatch = '://';
	protected $regexp = '#\\bhttps?://[-.\\w]+/[-./\\w]+\\.(?:gif|jpe?g|png)(?!\\S)#i';
	protected $tagName = 'IMG';
	protected function setUp()
	{
		if (isset($this->configurator->tags[$this->tagName]))
			return;
		$tag = $this->configurator->tags->add($this->tagName);
		$filter = $this->configurator->attributeFilters->get('#url');
		$tag->attributes->add($this->attrName)->filterChain->append($filter);
		$tag->template = '<img src="{@' . $this->attrName . '}"/>';
	}
}