<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\JavaScript\Minifiers;
use RuntimeException;
use s9e\TextFormatter\Configurator\JavaScript\OnlineMinifier;
class ClosureCompilerService extends OnlineMinifier
{
	public $compilationLevel = 'ADVANCED_OPTIMIZATIONS';
	public $excludeDefaultExterns = \true;
	public $externs;
	public $url = 'http://closure-compiler.appspot.com/compile';
	public function __construct()
	{
		parent::__construct();
		$this->externs = \file_get_contents(__DIR__ . '/../externs.service.js');
	}
	public function getCacheDifferentiator()
	{
		$key = array($this->compilationLevel, $this->excludeDefaultExterns);
		if ($this->excludeDefaultExterns)
			$key[] = $this->externs;
		return $key;
	}
	public function minify($src)
	{
		$body     = $this->generateRequestBody($src);
		$response = $this->query($body);
		if ($response === \false)
			throw new RuntimeException('Could not contact the Closure Compiler service');
		return $this->decodeResponse($response);
	}
	protected function decodeResponse($response)
	{
		$response = \json_decode($response, \true);
		if (\is_null($response))
		{
			$msgs = array(
					\JSON_ERROR_NONE => 'No error',
					\JSON_ERROR_DEPTH => 'Maximum stack depth exceeded',
					\JSON_ERROR_STATE_MISMATCH => 'State mismatch (invalid or malformed JSON)',
					\JSON_ERROR_CTRL_CHAR => 'Control character error, possibly incorrectly encoded',
					\JSON_ERROR_SYNTAX => 'Syntax error',
					\JSON_ERROR_UTF8 => 'Malformed UTF-8 characters, possibly incorrectly encoded'
				);
				throw new RuntimeException('Closure Compiler service returned invalid JSON: ' . (isset($msgs[\json_last_error()]) ? $msgs[\json_last_error()] : 'Unknown error'));
		}
		if (isset($response['serverErrors'][0]))
		{
			$error = $response['serverErrors'][0];
			throw new RuntimeException('Server error ' . $error['code'] . ': ' . $error['error']);
		}
		if (isset($response['errors'][0]))
		{
			$error = $response['errors'][0];
			throw new RuntimeException('Compilation error: ' . $error['error']);
		}
		return $response['compiledCode'];
	}
	protected function generateRequestBody($src)
	{
		$params = array(
			'compilation_level' => $this->compilationLevel,
			'js_code'           => $src,
			'output_format'     => 'json',
			'output_info'       => 'compiled_code'
		);
		if ($this->excludeDefaultExterns && $this->compilationLevel === 'ADVANCED_OPTIMIZATIONS')
		{
			$params['exclude_default_externs'] = 'true';
			$params['js_externs'] = $this->externs;
		}
		$body = \http_build_query($params) . '&output_info=errors';
		return $body;
	}
	protected function query($body)
	{
		return $this->httpClient->post(
			$this->url,
			array('Content-Type: application/x-www-form-urlencoded'),
			$body
		);
	}
}