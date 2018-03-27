<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins;
use s9e\TextFormatter\Parser;
abstract class ParserBase
{
	protected $config;
	protected $parser;
	final public function __construct(Parser $parser, array $config)
	{
		$this->parser = $parser;
		$this->config = $config;
		$this->setUp();
	}
	protected function setUp()
	{
	}
	abstract public function parse($text, array $matches);
}