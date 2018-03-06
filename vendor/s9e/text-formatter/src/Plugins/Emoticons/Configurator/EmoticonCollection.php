<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\Emoticons\Configurator;
use RuntimeException;
use s9e\TextFormatter\Configurator\Collections\NormalizedCollection;
use s9e\TextFormatter\Configurator\Helpers\TemplateHelper;
class EmoticonCollection extends NormalizedCollection
{
	protected $onDuplicateAction = 'replace';
	public function normalizeValue($value)
	{
		return TemplateHelper::saveTemplate(TemplateHelper::loadTemplate($value));
	}
	protected function getAlreadyExistsException($key)
	{
		return new RuntimeException("Emoticon '" . $key . "' already exists");
	}
	protected function getNotExistException($key)
	{
		return new RuntimeException("Emoticon '" . $key . "' does not exist");
	}
}