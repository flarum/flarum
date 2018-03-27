<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\HTMLEntities;
use s9e\TextFormatter\Plugins\ConfiguratorBase;
class Configurator extends ConfiguratorBase
{
	protected $attrName = 'char';
	protected $quickMatch = '&';
	protected $regexp = '/&(?>[a-z]+|#(?>[0-9]+|x[0-9a-f]+));/i';
	protected $tagName = 'HE';
	protected function setUp()
	{
		$tag = $this->configurator->tags->add($this->tagName);
		$tag->attributes->add($this->attrName);
		$tag->template
			= '<xsl:value-of select="@' . \htmlspecialchars($this->attrName) . '"/>';
	}
}