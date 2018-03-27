<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\MediaEmbed\Configurator\Collections;
use ArrayObject;
use s9e\TextFormatter\Configurator\ConfigProvider;
use s9e\TextFormatter\Configurator\JavaScript\Dictionary;
class SiteCollection extends ArrayObject implements ConfigProvider
{
	public function asConfig()
	{
		$map = array();
		foreach ($this as $siteId => $siteConfig)
		{
			if (isset($siteConfig['host']))
				foreach ((array) $siteConfig['host'] as $host)
					$map[$host] = $siteId;
			if (isset($siteConfig['scheme']))
				foreach ((array) $siteConfig['scheme'] as $scheme)
					$map[$scheme . ':'] = $siteId;
		}
		return new Dictionary($map);
	}
}