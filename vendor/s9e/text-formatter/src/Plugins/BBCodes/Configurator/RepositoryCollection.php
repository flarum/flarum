<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\BBCodes\Configurator;
use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Configurator\Collections\NormalizedCollection;
class RepositoryCollection extends NormalizedCollection
{
	protected $bbcodeMonkey;
	public function __construct(BBCodeMonkey $bbcodeMonkey)
	{
		$this->bbcodeMonkey = $bbcodeMonkey;
	}
	public function normalizeValue($value)
	{
		return ($value instanceof Repository)
		     ? $value
		     : new Repository($value, $this->bbcodeMonkey);
	}
}