<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Utils\Http\Clients;
use s9e\TextFormatter\Utils\Http\Client;
class Native extends Client
{
	public $gzipEnabled;
	public function __construct()
	{
		$this->gzipEnabled = \extension_loaded('zlib');
	}
	public function get($url, $headers = array())
	{
		return $this->request('GET', $url, $headers);
	}
	public function post($url, $headers = array(), $body = '')
	{
		return $this->request('POST', $url, $headers, $body);
	}
	protected function createContext($method, array $headers, $body)
	{
		$contextOptions = array(
			'ssl'  => array('verify_peer' => $this->sslVerifyPeer),
			'http' => array(
				'method'  => $method,
				'timeout' => $this->timeout,
				'header'  => $this->generateHeaders($headers, $body),
				'content' => $body
			)
		);
		return \stream_context_create($contextOptions);
	}
	protected function decompress($content)
	{
		if ($this->gzipEnabled && \substr($content, 0, 2) === "\x1f\x8b")
			return \gzdecode($content);
		return $content;
	}
	protected function generateHeaders(array $headers, $body)
	{
		if ($this->gzipEnabled)
			$headers[] = 'Accept-Encoding: gzip';
		$headers[] = 'Content-Length: ' . \strlen($body);
		return $headers;
	}
	protected function request($method, $url, $headers, $body = '')
	{
		$response = @\file_get_contents($url, \false, $this->createContext($method, $headers, $body));
		return (\is_string($response)) ? $this->decompress($response) : $response;
	}
}