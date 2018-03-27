<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Parser;
class BuiltInFilters
{
	public static function filterAlnum($attrValue)
	{
		return \filter_var($attrValue, \FILTER_VALIDATE_REGEXP, array(
			'options' => array('regexp' => '/^[0-9A-Za-z]+$/D')
		));
	}
	public static function filterColor($attrValue)
	{
		return \filter_var($attrValue, \FILTER_VALIDATE_REGEXP, array(
			'options' => array(
				'regexp' => '/^(?>#[0-9a-f]{3,6}|rgb\\(\\d{1,3}, *\\d{1,3}, *\\d{1,3}\\)|[a-z]+)$/Di'
			)
		));
	}
	public static function filterEmail($attrValue)
	{
		return \filter_var($attrValue, \FILTER_VALIDATE_EMAIL);
	}
	public static function filterFalse($attrValue)
	{
		return \false;
	}
	public static function filterFloat($attrValue)
	{
		return \filter_var($attrValue, \FILTER_VALIDATE_FLOAT);
	}
	public static function filterHashmap($attrValue, array $map, $strict)
	{
		if (isset($map[$attrValue]))
			return $map[$attrValue];
		return ($strict) ? \false : $attrValue;
	}
	public static function filterIdentifier($attrValue)
	{
		return \filter_var($attrValue, \FILTER_VALIDATE_REGEXP, array(
			'options' => array('regexp' => '/^[-0-9A-Za-z_]+$/D')
		));
	}
	public static function filterInt($attrValue)
	{
		return \filter_var($attrValue, \FILTER_VALIDATE_INT);
	}
	public static function filterIp($attrValue)
	{
		return \filter_var($attrValue, \FILTER_VALIDATE_IP);
	}
	public static function filterIpport($attrValue)
	{
		if (\preg_match('/^\\[([^\\]]+)(\\]:[1-9][0-9]*)$/D', $attrValue, $m))
		{
			$ip = self::filterIpv6($m[1]);
			if ($ip === \false)
				return \false;
			return '[' . $ip . $m[2];
		}
		if (\preg_match('/^([^:]+)(:[1-9][0-9]*)$/D', $attrValue, $m))
		{
			$ip = self::filterIpv4($m[1]);
			if ($ip === \false)
				return \false;
			return $ip . $m[2];
		}
		return \false;
	}
	public static function filterIpv4($attrValue)
	{
		return \filter_var($attrValue, \FILTER_VALIDATE_IP, \FILTER_FLAG_IPV4);
	}
	public static function filterIpv6($attrValue)
	{
		return \filter_var($attrValue, \FILTER_VALIDATE_IP, \FILTER_FLAG_IPV6);
	}
	public static function filterMap($attrValue, array $map)
	{
		foreach ($map as $pair)
			if (\preg_match($pair[0], $attrValue))
				return $pair[1];
		return $attrValue;
	}
	public static function filterNumber($attrValue)
	{
		return \filter_var($attrValue, \FILTER_VALIDATE_REGEXP, array(
			'options' => array('regexp' => '/^[0-9]+$/D')
		));
	}
	public static function filterRange($attrValue, $min, $max, Logger $logger = \null)
	{
		$attrValue = \filter_var($attrValue, \FILTER_VALIDATE_INT);
		if ($attrValue === \false)
			return \false;
		if ($attrValue < $min)
		{
			if (isset($logger))
				$logger->warn(
					'Value outside of range, adjusted up to min value',
					array(
						'attrValue' => $attrValue,
						'min'       => $min,
						'max'       => $max
					)
				);
			return $min;
		}
		if ($attrValue > $max)
		{
			if (isset($logger))
				$logger->warn(
					'Value outside of range, adjusted down to max value',
					array(
						'attrValue' => $attrValue,
						'min'       => $min,
						'max'       => $max
					)
				);
			return $max;
		}
		return $attrValue;
	}
	public static function filterRegexp($attrValue, $regexp)
	{
		return \filter_var($attrValue, \FILTER_VALIDATE_REGEXP, array(
			'options' => array('regexp' => $regexp)
		));
	}
	public static function filterSimpletext($attrValue)
	{
		return \filter_var($attrValue, \FILTER_VALIDATE_REGEXP, array(
			'options' => array('regexp' => '/^[- +,.0-9A-Za-z_]+$/D')
		));
	}
	public static function filterUint($attrValue)
	{
		return \filter_var($attrValue, \FILTER_VALIDATE_INT, array(
			'options' => array('min_range' => 0)
		));
	}
	public static function filterUrl($attrValue, array $urlConfig, Logger $logger = \null)
	{
		$p = self::parseUrl(\trim($attrValue));
		$error = self::validateUrl($urlConfig, $p);
		if (!empty($error))
		{
			if (isset($logger))
			{
				$p['attrValue'] = $attrValue;
				$logger->err($error, $p);
			}
			return \false;
		}
		return self::rebuildUrl($p);
	}
	protected static function parseUrl($url)
	{
		$regexp = '(^(?:([a-z][-+.\\w]*):)?(?://(?:([^:/?#]*)(?::([^/?#]*)?)?@)?(?:(\\[[a-f\\d:]+\\]|[^:/?#]+)(?::(\\d*))?)?(?![^/?#]))?([^?#]*)(\\?[^#]*)?(#.*)?$)Di';
		\preg_match($regexp, $url, $m);
		$parts  = array();
		$tokens = array('scheme', 'user', 'pass', 'host', 'port', 'path', 'query', 'fragment');
		foreach ($tokens as $i => $name)
			$parts[$name] = (isset($m[$i + 1])) ? $m[$i + 1] : '';
		$parts['scheme'] = \strtolower($parts['scheme']);
		$parts['host'] = \rtrim(\preg_replace("/\xE3\x80\x82|\xEF(?:\xBC\x8E|\xBD\xA1)/s", '.', $parts['host']), '.');
		if (\preg_match('#[^[:ascii:]]#', $parts['host']) && \function_exists('idn_to_ascii'))
			$parts['host'] = \idn_to_ascii($parts['host']);
		return $parts;
	}
	protected static function rebuildUrl(array $p)
	{
		$url = '';
		if ($p['scheme'] !== '')
			$url .= $p['scheme'] . ':';
		if ($p['host'] === '')
		{
			if ($p['scheme'] === 'file')
				$url .= '//';
		}
		else
		{
			$url .= '//';
			if ($p['user'] !== '')
			{
				$url .= \rawurlencode(\urldecode($p['user']));
				if ($p['pass'] !== '')
					$url .= ':' . \rawurlencode(\urldecode($p['pass']));
				$url .= '@';
			}
			$url .= $p['host'];
			if ($p['port'] !== '')
				$url .= ':' . $p['port'];
		}
		$path = $p['path'] . $p['query'] . $p['fragment'];
		$path = \preg_replace_callback(
			'/%.?[a-f]/',
			function ($m)
			{
				return \strtoupper($m[0]);
			},
			$path
		);
		$url .= self::sanitizeUrl($path);
		if (!$p['scheme'])
			$url = \preg_replace('#^([^/]*):#', '$1%3A', $url);
		return $url;
	}
	public static function sanitizeUrl($url)
	{
		return \preg_replace_callback(
			'/%(?![0-9A-Fa-f]{2})|[^!#-&*-;=?-Z_a-z]/S',
			function ($m)
			{
				return \rawurlencode($m[0]);
			},
			$url
		);
	}
	protected static function validateUrl(array $urlConfig, array $p)
	{
		if ($p['scheme'] !== '' && !\preg_match($urlConfig['allowedSchemes'], $p['scheme']))
			return 'URL scheme is not allowed';
		if ($p['host'] === '')
		{
			if ($p['scheme'] !== 'file' && $p['scheme'] !== '')
				return 'Missing host';
		}
		else
		{
			$regexp = '/^(?!-)[-a-z0-9]{0,62}[a-z0-9](?:\\.(?!-)[-a-z0-9]{0,62}[a-z0-9])*$/i';
			if (!\preg_match($regexp, $p['host']))
				if (!self::filterIpv4($p['host'])
				 && !self::filterIpv6(\preg_replace('/^\\[(.*)\\]$/', '$1', $p['host'])))
					return 'URL host is invalid';
			if ((isset($urlConfig['disallowedHosts']) && \preg_match($urlConfig['disallowedHosts'], $p['host']))
			 || (isset($urlConfig['restrictedHosts']) && !\preg_match($urlConfig['restrictedHosts'], $p['host'])))
				return 'URL host is not allowed';
		}
	}
}