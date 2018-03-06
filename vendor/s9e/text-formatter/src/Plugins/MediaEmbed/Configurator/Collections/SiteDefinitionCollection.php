<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\MediaEmbed\Configurator\Collections;
use InvalidArgumentException;
use RuntimeException;
use s9e\TextFormatter\Configurator\Collections\NormalizedCollection;
class SiteDefinitionCollection extends NormalizedCollection
{
	protected $onDuplicateAction = 'replace';
	protected function getAlreadyExistsException($key)
	{
		return new RuntimeException("Media site '" . $key . "' already exists");
	}
	protected function getNotExistException($key)
	{
		return new RuntimeException("Media site '" . $key . "' does not exist");
	}
	public function normalizeKey($siteId)
	{
		$siteId = \strtolower($siteId);
		if (!\preg_match('(^[a-z0-9]+$)', $siteId))
			throw new InvalidArgumentException('Invalid site ID');
		return $siteId;
	}
	public function normalizeValue($siteConfig)
	{
		if (!\is_array($siteConfig))
			throw new InvalidArgumentException('Invalid site definition type');
		return $siteConfig;
	}
}