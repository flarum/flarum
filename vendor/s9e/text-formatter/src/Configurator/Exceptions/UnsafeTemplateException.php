<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Exceptions;
use DOMNode;
use s9e\TextFormatter\Configurator\Helpers\TemplateHelper;
class UnsafeTemplateException extends InvalidTemplateException
{
	protected $node;
	public function __construct($msg, DOMNode $node)
	{
		parent::__construct($msg);
		$this->node = $node;
	}
	public function getNode()
	{
		return $this->node;
	}
	public function highlightNode($prepend = '<span style="background-color:#ff0">', $append = '</span>')
	{
		return TemplateHelper::highlightNode($this->node, $prepend, $append);
	}
	public function setNode(DOMNode $node)
	{
		$this->node = $node;
	}
}