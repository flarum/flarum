<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateChecks;
use DOMElement;
use DOMXPath;
use s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\TemplateCheck;
class DisallowNodeByXPath extends TemplateCheck
{
	public $query;
	public function __construct($query)
	{
		$this->query = $query;
	}
	public function check(DOMElement $template, Tag $tag)
	{
		$xpath = new DOMXPath($template->ownerDocument);
		foreach ($xpath->query($this->query) as $node)
			throw new UnsafeTemplateException("Node '" . $node->nodeName . "' is disallowed because it matches '" . $this->query . "'", $node);
	}
}