<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Collections;
use InvalidArgumentException;
use ReflectionClass;
use s9e\TextFormatter\Configurator\JavaScript\Minifier;
class MinifierList extends NormalizedList
{
	public function normalizeValue($minifier)
	{
		if (\is_string($minifier))
			$minifier = $this->getMinifierInstance($minifier);
		elseif (\is_array($minifier) && !empty($minifier[0]))
			$minifier = $this->getMinifierInstance($minifier[0], \array_slice($minifier, 1));
		if (!($minifier instanceof Minifier))
			throw new InvalidArgumentException('Invalid minifier ' . \var_export($minifier, \true));
		return $minifier;
	}
	protected function getMinifierInstance($name, array $args = array())
	{
		$className = 's9e\\TextFormatter\\Configurator\\JavaScript\\Minifiers\\' . $name;
		if (!\class_exists($className))
			throw new InvalidArgumentException('Invalid minifier ' . \var_export($name, \true));
		$reflection = new ReflectionClass($className);
		$minifier   = (empty($args)) ? $reflection->newInstance() : $reflection->newInstanceArgs($args);
		return $minifier;
	}
}