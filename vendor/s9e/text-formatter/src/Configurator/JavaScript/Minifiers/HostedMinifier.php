<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\JavaScript\Minifiers;
use RuntimeException;
use s9e\TextFormatter\Configurator\JavaScript\OnlineMinifier;
class HostedMinifier extends OnlineMinifier
{
	public $gzLevel = 5;
	public $url = 'http://s9e-textformatter.rhcloud.com/minifier/';
	public function minify($src)
	{
		$headers = array('Content-Type: application/octet-stream');
		$body    = $src;
		if (\extension_loaded('zlib'))
		{
			$headers[] = 'Content-Encoding: gzip';
			$body      = \gzencode($body, $this->gzLevel);
		}
		$code = $this->httpClient->post($this->url, $headers, $body);
		if ($code === \false)
			throw new RuntimeException;
		return $code;
	}
}