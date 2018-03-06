<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\Autovideo;
use s9e\TextFormatter\Configurator\Helpers\RegexpBuilder;
use s9e\TextFormatter\Plugins\ConfiguratorBase;
class Configurator extends ConfiguratorBase
{
	protected $attrName = 'src';
	protected $quickMatch = '://';
	protected $regexp = '#\\bhttps?://[-.\\w]+/[-./\\w]+\\.(?:mp4|ogg|webm)(?!\\S)#i';
	protected $tagName = 'VIDEO';
	protected function setUp()
	{
		if (isset($this->configurator->tags[$this->tagName]))
			return;
		$tag = $this->configurator->tags->add($this->tagName);
		$filter = $this->configurator->attributeFilters['#url'];
		$tag->attributes->add($this->attrName)->filterChain->append($filter);
		$tag->template = '<video src="{@' . $this->attrName . '}"/>';
	}
}