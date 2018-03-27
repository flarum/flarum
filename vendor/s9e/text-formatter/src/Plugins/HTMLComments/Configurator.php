<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\HTMLComments;
use s9e\TextFormatter\Plugins\ConfiguratorBase;
class Configurator extends ConfiguratorBase
{
	protected $attrName = 'content';
	protected $quickMatch = '<!--';
	protected $regexp = '/<!--(?!\\[if).*?-->/is';
	protected $tagName = 'HC';
	protected function setUp()
	{
		$tag = $this->configurator->tags->add($this->tagName);
		$tag->attributes->add($this->attrName);
		$tag->rules->ignoreTags();
		$tag->template = '<xsl:comment><xsl:value-of select="@' . \htmlspecialchars($this->attrName) . '"/></xsl:comment>';
	}
}