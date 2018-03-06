<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\JavaScript\Minifiers;
use MatthiasMullie\Minify;
use s9e\TextFormatter\Configurator\JavaScript\Minifier;
class MatthiasMullieMinify extends Minifier
{
	public function minify($src)
	{
		$minifier = new Minify\JS($src);
		return $minifier->minify();
	}
}