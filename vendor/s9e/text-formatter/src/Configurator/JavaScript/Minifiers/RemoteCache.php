<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\JavaScript\Minifiers;
use RuntimeException;
use s9e\TextFormatter\Configurator\JavaScript\OnlineMinifier;
class RemoteCache extends OnlineMinifier
{
	public $url = 'http://s9e-textformatter.rhcloud.com/minifier/';
	public function minify($src)
	{
		$url  = $this->url . '?hash=' . $this->getHash($src);
		$code = $this->httpClient->get($url);
		if ($code === \false)
			throw new RuntimeException;
		return $code;
	}
	protected function getHash($src)
	{
		return \strtr(\base64_encode(\sha1($src, \true) . \md5($src, \true)), '+/', '-_');
	}
}