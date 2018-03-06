<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\Escaper;
use s9e\TextFormatter\Plugins\ConfiguratorBase;
class Configurator extends ConfiguratorBase
{
	protected $quickMatch = '\\';
	protected $regexp;
	protected $tagName = 'ESC';
	public function escapeAll($bool = \true)
	{
		$this->regexp = ($bool) ? '/\\\\./su' : '/\\\\[-!#()*+.:<>@[\\\\\\]^_`{|}]/';
	}
	protected function setUp()
	{
		$this->escapeAll(\false);
		$tag = $this->configurator->tags->add($this->tagName);
		$tag->rules->disableAutoLineBreaks();
		$tag->rules->ignoreTags();
		$tag->rules->preventLineBreaks();
		$tag->template = '<xsl:apply-templates/>';
	}
}