<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter;
function autoload($className)
{
	if (\strpos($className, 's9e\\TextFormatter\\') === 0
	 && \strpos($className, '.') === \false)
	{
		$path = __DIR__ . \strtr(\substr($className, 17), '\\', '/') . '.php';
		if (\file_exists($path))
			include $path;
		elseif (!\class_exists('s9e\\TextFormatter\\Configurator', \false)
			 && \strpos($className, 's9e\\TextFormatter\\Configurator\\') === 0)
			include __DIR__ . '/Configurator.php';
	}
}
\spl_autoload_register('s9e\\TextFormatter\\autoload');