<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Renderers;
use s9e\TextFormatter\Renderer;
class Unformatted extends Renderer
{
	public $metaElementsRegexp = '((?!))';
	protected function renderRichText($xml)
	{
		return \str_replace("\n", "<br>\n", \htmlspecialchars(\strip_tags($xml), \ENT_COMPAT, 'UTF-8', \false));
	}
}