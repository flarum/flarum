<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RendererGenerators\XSLT;
use s9e\TextFormatter\Configurator\TemplateNormalizer;
class Optimizer
{
	public $normalizer;
	public function __construct()
	{
		$this->normalizer = new TemplateNormalizer;
		$this->normalizer->clear();
		$this->normalizer->append('MergeConsecutiveCopyOf');
		$this->normalizer->append('MergeIdenticalConditionalBranches');
		$this->normalizer->append('OptimizeNestedConditionals');
	}
	public function optimizeTemplate($template)
	{
		return $this->normalizer->normalizeTemplate($template);
	}
}