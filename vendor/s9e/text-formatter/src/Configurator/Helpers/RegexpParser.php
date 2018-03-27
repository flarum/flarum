<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Helpers;
use RuntimeException;
abstract class RegexpParser
{
	public static function getAllowedCharacterRegexp($regexp)
	{
		$def = self::parse($regexp);
		if (\strpos($def['modifiers'], 'm') !== \false)
			return '//';
		if (\substr($def['regexp'], 0, 1) !== '^'
		 || \substr($def['regexp'], -1)   !== '$')
			return '//';
		$def['tokens'][] = array(
			'pos'  => \strlen($def['regexp']),
			'len'  => 0,
			'type' => 'end'
		);
		$patterns = array();
		$literal = '';
		$pos     = 0;
		$skipPos = 0;
		$depth   = 0;
		foreach ($def['tokens'] as $token)
		{
			if ($token['type'] === 'option')
				$skipPos = \max($skipPos, $token['pos'] + $token['len']);
			if (\strpos($token['type'], 'AssertionStart') !== \false)
			{
				$endToken = $def['tokens'][$token['endToken']];
				$skipPos  = \max($skipPos, $endToken['pos'] + $endToken['len']);
			}
			if ($token['pos'] >= $skipPos)
			{
				if ($token['type'] === 'characterClass')
					$patterns[] = '[' . $token['content'] . ']';
				if ($token['pos'] > $pos)
				{
					$tmp = \substr($def['regexp'], $pos, $token['pos'] - $pos);
					$literal .= $tmp;
					if (!$depth)
					{
						$tmp = \str_replace('\\\\', '', $tmp);
						if (\preg_match('/(?<!\\\\)\\|(?!\\^)/', $tmp))
							return '//';
						if (\preg_match('/(?<![$\\\\])\\|/', $tmp))
							return '//';
					}
				}
			}
			if (\substr($token['type'], -5) === 'Start')
				++$depth;
			elseif (\substr($token['type'], -3) === 'End')
				--$depth;
			$pos = \max($skipPos, $token['pos'] + $token['len']);
		}
		if (\preg_match('#(?<!\\\\)(?:\\\\\\\\)*\\.#', $literal))
		{
			if (\strpos($def['modifiers'], 's') !== \false
			 || \strpos($literal, "\n") !== \false)
				return '//';
			$patterns[] = '.';
			$literal = \preg_replace('#(?<!\\\\)((?:\\\\\\\\)*)\\.#', '$1', $literal);
		}
		$literal = \preg_replace('#(?<!\\\\)((?:\\\\\\\\)*)[*+?]#', '$1', $literal);
		$literal = \preg_replace('#(?<!\\\\)((?:\\\\\\\\)*)\\{[^}]+\\}#', '$1', $literal);
		$literal = \preg_replace('#(?<!\\\\)((?:\\\\\\\\)*)\\\\[bBAZzG1-9]#', '$1', $literal);
		$literal = \preg_replace('#(?<!\\\\)((?:\\\\\\\\)*)[$^|]#', '$1', $literal);
		$literal = \preg_replace('#(?<!\\\\)((?:\\\\\\\\)*)([-^\\]])#', '$1\\\\$2', $literal);
		if (\strpos($def['modifiers'], 'D') === \false)
			$literal .= "\n";
		if ($literal !== '')
			$patterns[] = '[' . $literal . ']';
		if (empty($patterns))
			return '/^$/D';
		$regexp = $def['delimiter'] . \implode('|', $patterns) . $def['delimiter'];
		if (\strpos($def['modifiers'], 'i') !== \false)
			$regexp .= 'i';
		if (\strpos($def['modifiers'], 'u') !== \false)
			$regexp .= 'u';
		return $regexp;
	}
	public static function getCaptureNames($regexp)
	{
		$map        = array('');
		$regexpInfo = self::parse($regexp);
		foreach ($regexpInfo['tokens'] as $tok)
			if ($tok['type'] === 'capturingSubpatternStart')
				$map[] = (isset($tok['name'])) ? $tok['name'] : '';
		return $map;
	}
	public static function parse($regexp)
	{
		if (!\preg_match('#^(.)(.*?)\\1([a-zA-Z]*)$#Ds', $regexp, $m))
			throw new RuntimeException('Could not parse regexp delimiters');
		$ret = array(
			'delimiter' => $m[1],
			'modifiers' => $m[3],
			'regexp'    => $m[2],
			'tokens'    => array()
		);
		$regexp = $m[2];
		$openSubpatterns = array();
		$pos = 0;
		$regexpLen = \strlen($regexp);
		while ($pos < $regexpLen)
		{
			switch ($regexp[$pos])
			{
				case '\\':
					$pos += 2;
					break;
				case '[':
					if (!\preg_match('#\\[(.*?(?<!\\\\)(?:\\\\\\\\)*+)\\]((?:[+*][+?]?|\\?)?)#', $regexp, $m, 0, $pos))
						throw new RuntimeException('Could not find matching bracket from pos ' . $pos);
					$ret['tokens'][] = array(
						'pos'         => $pos,
						'len'         => \strlen($m[0]),
						'type'        => 'characterClass',
						'content'     => $m[1],
						'quantifiers' => $m[2]
					);
					$pos += \strlen($m[0]);
					break;
				case '(':
					if (\preg_match('#\\(\\?([a-z]*)\\)#i', $regexp, $m, 0, $pos))
					{
						$ret['tokens'][] = array(
							'pos'     => $pos,
							'len'     => \strlen($m[0]),
							'type'    => 'option',
							'options' => $m[1]
						);
						$pos += \strlen($m[0]);
						break;
					}
					if (\preg_match("#(?J)\\(\\?(?:P?<(?<name>[a-z_0-9]+)>|'(?<name>[a-z_0-9]+)')#A", $regexp, $m, \PREG_OFFSET_CAPTURE, $pos))
					{
						$tok = array(
							'pos'  => $pos,
							'len'  => \strlen($m[0][0]),
							'type' => 'capturingSubpatternStart',
							'name' => $m['name'][0]
						);
						$pos += \strlen($m[0][0]);
					}
					elseif (\preg_match('#\\(\\?([a-z]*):#iA', $regexp, $m, 0, $pos))
					{
						$tok = array(
							'pos'     => $pos,
							'len'     => \strlen($m[0]),
							'type'    => 'nonCapturingSubpatternStart',
							'options' => $m[1]
						);
						$pos += \strlen($m[0]);
					}
					elseif (\preg_match('#\\(\\?>#iA', $regexp, $m, 0, $pos))
					{
						$tok = array(
							'pos'     => $pos,
							'len'     => \strlen($m[0]),
							'type'    => 'nonCapturingSubpatternStart',
							'subtype' => 'atomic'
						);
						$pos += \strlen($m[0]);
					}
					elseif (\preg_match('#\\(\\?(<?[!=])#A', $regexp, $m, 0, $pos))
					{
						$assertions = array(
							'='  => 'lookahead',
							'<=' => 'lookbehind',
							'!'  => 'negativeLookahead',
							'<!' => 'negativeLookbehind'
						);
						$tok = array(
							'pos'     => $pos,
							'len'     => \strlen($m[0]),
							'type'    => $assertions[$m[1]] . 'AssertionStart'
						);
						$pos += \strlen($m[0]);
					}
					elseif (\preg_match('#\\(\\?#A', $regexp, $m, 0, $pos))
						throw new RuntimeException('Unsupported subpattern type at pos ' . $pos);
					else
					{
						$tok = array(
							'pos'  => $pos,
							'len'  => 1,
							'type' => 'capturingSubpatternStart'
						);
						++$pos;
					}
					$openSubpatterns[] = \count($ret['tokens']);
					$ret['tokens'][] = $tok;
					break;
				case ')':
					if (empty($openSubpatterns))
						throw new RuntimeException('Could not find matching pattern start for right parenthesis at pos ' . $pos);
					$k = \array_pop($openSubpatterns);
					$startToken =& $ret['tokens'][$k];
					$startToken['endToken'] = \count($ret['tokens']);
					$startToken['content']  = \substr(
						$regexp,
						$startToken['pos'] + $startToken['len'],
						$pos - ($startToken['pos'] + $startToken['len'])
					);
					$spn = \strspn($regexp, '+*?', 1 + $pos);
					$quantifiers = \substr($regexp, 1 + $pos, $spn);
					$ret['tokens'][] = array(
						'pos'  => $pos,
						'len'  => 1 + $spn,
						'type' => \substr($startToken['type'], 0, -5) . 'End',
						'quantifiers' => $quantifiers
					);
					unset($startToken);
					$pos += 1 + $spn;
					break;
				default:
					++$pos;
			}
		}
		if (!empty($openSubpatterns))
			throw new RuntimeException('Could not find matching pattern end for left parenthesis at pos ' . $ret['tokens'][$openSubpatterns[0]]['pos']);
		return $ret;
	}
}