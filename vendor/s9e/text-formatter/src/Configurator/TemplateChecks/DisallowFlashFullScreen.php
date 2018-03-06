<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateChecks;
class DisallowFlashFullScreen extends AbstractFlashRestriction
{
	public $defaultSetting = 'false';
	protected $settingName = 'allowFullScreen';
	protected $settings = array(
		'true'  => 1,
		'false' => 0
	);
	public function __construct($onlyIfDynamic = \false)
	{
		parent::__construct('false', $onlyIfDynamic);
	}
}