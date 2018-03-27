<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Items;
use DOMDocument;
use s9e\TextFormatter\Configurator\Helpers\TemplateHelper;
class TemplateDocument extends DOMDocument
{
	protected $template;
	public function __construct(Template $template)
	{
		$this->template = $template;
	}
	public function saveChanges()
	{
		$this->template->setContent(TemplateHelper::saveTemplate($this));
	}
}