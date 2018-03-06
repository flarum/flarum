<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter;
use InvalidArgumentException;
use RuntimeException;
use s9e\TextFormatter\Configurator\BundleGenerator;
use s9e\TextFormatter\Configurator\Collections\AttributeFilterCollection;
use s9e\TextFormatter\Configurator\Collections\PluginCollection;
use s9e\TextFormatter\Configurator\Collections\Ruleset;
use s9e\TextFormatter\Configurator\Collections\TagCollection;
use s9e\TextFormatter\Configurator\ConfigProvider;
use s9e\TextFormatter\Configurator\Helpers\ConfigHelper;
use s9e\TextFormatter\Configurator\Helpers\RulesHelper;
use s9e\TextFormatter\Configurator\JavaScript;
use s9e\TextFormatter\Configurator\JavaScript\Dictionary;
use s9e\TextFormatter\Configurator\Rendering;
use s9e\TextFormatter\Configurator\RulesGenerator;
use s9e\TextFormatter\Configurator\TemplateChecker;
use s9e\TextFormatter\Configurator\TemplateNormalizer;
use s9e\TextFormatter\Configurator\UrlConfig;
class Configurator implements ConfigProvider
{
	public $attributeFilters;
	public $bundleGenerator;
	public $javascript;
	public $plugins;
	public $registeredVars;
	public $rendering;
	public $rootRules;
	public $rulesGenerator;
	public $tags;
	public $templateChecker;
	public $templateNormalizer;
	public function __construct()
	{
		$this->attributeFilters   = new AttributeFilterCollection;
		$this->bundleGenerator    = new BundleGenerator($this);
		$this->plugins            = new PluginCollection($this);
		$this->registeredVars     = array('urlConfig' => new UrlConfig);
		$this->rendering          = new Rendering($this);
		$this->rootRules          = new Ruleset;
		$this->rulesGenerator     = new RulesGenerator;
		$this->tags               = new TagCollection;
		$this->templateChecker    = new TemplateChecker;
		$this->templateNormalizer = new TemplateNormalizer;
	}
	public function __get($k)
	{
		if (\preg_match('#^[A-Z][A-Za-z_0-9]+$#D', $k))
			return (isset($this->plugins[$k]))
			     ? $this->plugins[$k]
			     : $this->plugins->load($k);
		if (isset($this->registeredVars[$k]))
			return $this->registeredVars[$k];
		throw new RuntimeException("Undefined property '" . __CLASS__ . '::$' . $k . "'");
	}
	public function __isset($k)
	{
		if (\preg_match('#^[A-Z][A-Za-z_0-9]+$#D', $k))
			return isset($this->plugins[$k]);
		return isset($this->registeredVars[$k]);
	}
	public function __set($k, $v)
	{
		if (\preg_match('#^[A-Z][A-Za-z_0-9]+$#D', $k))
			$this->plugins[$k] = $v;
		else
			$this->registeredVars[$k] = $v;
	}
	public function __unset($k)
	{
		if (\preg_match('#^[A-Z][A-Za-z_0-9]+$#D', $k))
			unset($this->plugins[$k]);
		else
			unset($this->registeredVars[$k]);
	}
	public function enableJavaScript()
	{
		if (!isset($this->javascript))
			$this->javascript = new JavaScript($this);
	}
	public function finalize(array $options = array())
	{
		$return = array();
		$options += array(
			'addHTML5Rules'  => \true,
			'optimizeConfig' => \true,
			'returnJS'       => isset($this->javascript),
			'returnParser'   => \true,
			'returnRenderer' => \true
		);
		if ($options['addHTML5Rules'])
			$this->addHTML5Rules($options);
		if ($options['returnRenderer'])
		{
			$renderer = $this->rendering->getRenderer();
			if (isset($options['finalizeRenderer']))
				\call_user_func($options['finalizeRenderer'], $renderer);
			$return['renderer'] = $renderer;
		}
		if ($options['returnJS'] || $options['returnParser'])
		{
			$config = $this->asConfig();
			if ($options['returnJS'])
				$return['js'] = $this->javascript->getParser(ConfigHelper::filterConfig($config, 'JS'));
			if ($options['returnParser'])
			{
				$config = ConfigHelper::filterConfig($config, 'PHP');
				if ($options['optimizeConfig'])
					ConfigHelper::optimizeArray($config);
				$parser = new Parser($config);
				if (isset($options['finalizeParser']))
					\call_user_func($options['finalizeParser'], $parser);
				$return['parser'] = $parser;
			}
		}
		return $return;
	}
	public function loadBundle($bundleName)
	{
		if (!\preg_match('#^[A-Z][A-Za-z0-9]+$#D', $bundleName))
			throw new InvalidArgumentException("Invalid bundle name '" . $bundleName . "'");
		$className = __CLASS__ . '\\Bundles\\' . $bundleName;
		$bundle = new $className;
		$bundle->configure($this);
	}
	public function saveBundle($className, $filepath, array $options = array())
	{
		$file = "<?php\n\n" . $this->bundleGenerator->generate($className, $options);
		return (\file_put_contents($filepath, $file) !== \false);
	}
	public function addHTML5Rules(array $options = array())
	{
		$options += array('rootRules' => $this->rootRules);
		$this->plugins->finalize();
		foreach ($this->tags as $tag)
			$this->templateNormalizer->normalizeTag($tag);
		$rules = $this->rulesGenerator->getRules($this->tags, $options);
		$this->rootRules->merge($rules['root'], \false);
		foreach ($rules['tags'] as $tagName => $tagRules)
			$this->tags[$tagName]->rules->merge($tagRules, \false);
	}
	public function asConfig()
	{
		$this->plugins->finalize();
		$properties = \get_object_vars($this);
		unset($properties['attributeFilters']);
		unset($properties['bundleGenerator']);
		unset($properties['javascript']);
		unset($properties['rendering']);
		unset($properties['rulesGenerator']);
		unset($properties['registeredVars']);
		unset($properties['templateChecker']);
		unset($properties['templateNormalizer']);
		unset($properties['stylesheet']);
		$config    = ConfigHelper::toArray($properties);
		$bitfields = RulesHelper::getBitfields($this->tags, $this->rootRules);
		$config['rootContext'] = $bitfields['root'];
		$config['rootContext']['flags'] = $config['rootRules']['flags'];
		$config['registeredVars'] = ConfigHelper::toArray($this->registeredVars, \true);
		$config += array(
			'plugins' => array(),
			'tags'    => array()
		);
		$config['tags'] = \array_intersect_key($config['tags'], $bitfields['tags']);
		foreach ($bitfields['tags'] as $tagName => $tagBitfields)
			$config['tags'][$tagName] += $tagBitfields;
		unset($config['rootRules']);
		return $config;
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Configurator\RendererGenerators\PHP;
class BundleGenerator
{
	protected $configurator;
	public $serializer = 'serialize';
	public $unserializer = 'unserialize';
	public function __construct(Configurator $configurator)
	{
		$this->configurator = $configurator;
	}
	public function generate($className, array $options = array())
	{
		$options += array('autoInclude' => \true);
		$objects  = $this->configurator->finalize($options);
		$parser   = $objects['parser'];
		$renderer = $objects['renderer'];
		$namespace = '';
		if (\preg_match('#(.*)\\\\([^\\\\]+)$#', $className, $m))
		{
			$namespace = $m[1];
			$className = $m[2];
		}
		$php = array();
		$php[] = '/**';
		$php[] = '* @package   s9e\TextFormatter';
		$php[] = '* @copyright Copyright (c) 2010-2016 The s9e Authors';
		$php[] = '* @license   http://www.opensource.org/licenses/mit-license.php The MIT License';
		$php[] = '*/';
		if ($namespace)
		{
			$php[] = 'namespace ' . $namespace . ';';
			$php[] = '';
		}
		$php[] = 'abstract class ' . $className . ' extends \\s9e\\TextFormatter\\Bundle';
		$php[] = '{';
		$php[] = '	/**';
		$php[] = '	* @var s9e\\TextFormatter\\Parser Singleton instance used by parse()';
		$php[] = '	*/';
		$php[] = '	protected static $parser;';
		$php[] = '';
		$php[] = '	/**';
		$php[] = '	* @var s9e\\TextFormatter\\Renderer Singleton instance used by render()';
		$php[] = '	*/';
		$php[] = '	protected static $renderer;';
		$php[] = '';
		$events = array(
			'beforeParse'
				=> 'Callback executed before parse(), receives the original text as argument',
			'afterParse'
				=> 'Callback executed after parse(), receives the parsed text as argument',
			'beforeRender'
				=> 'Callback executed before render(), receives the parsed text as argument',
			'afterRender'
				=> 'Callback executed after render(), receives the output as argument',
			'beforeUnparse'
				=> 'Callback executed before unparse(), receives the parsed text as argument',
			'afterUnparse'
				=> 'Callback executed after unparse(), receives the original text as argument'
		);
		foreach ($events as $eventName => $eventDesc)
			if (isset($options[$eventName]))
			{
				$php[] = '	/**';
				$php[] = '	* @var ' . $eventDesc;
				$php[] = '	*/';
				$php[] = '	public static $' . $eventName . ' = ' . \var_export($options[$eventName], \true) . ';';
				$php[] = '';
			}
		$php[] = '	/**';
		$php[] = '	* Return a new instance of s9e\\TextFormatter\\Parser';
		$php[] = '	*';
		$php[] = '	* @return s9e\\TextFormatter\\Parser';
		$php[] = '	*/';
		$php[] = '	public static function getParser()';
		$php[] = '	{';
		if (isset($options['parserSetup']))
		{
			$php[] = '		$parser = ' . $this->exportObject($parser) . ';';
			$php[] = '		' . $this->exportCallback($namespace, $options['parserSetup'], '$parser') . ';';
			$php[] = '';
			$php[] = '		return $parser;';
		}
		else
			$php[] = '		return ' . $this->exportObject($parser) . ';';
		$php[] = '	}';
		$php[] = '';
		$php[] = '	/**';
		$php[] = '	* Return a new instance of s9e\\TextFormatter\\Renderer';
		$php[] = '	*';
		$php[] = '	* @return s9e\\TextFormatter\\Renderer';
		$php[] = '	*/';
		$php[] = '	public static function getRenderer()';
		$php[] = '	{';
		if (!empty($options['autoInclude'])
		 && $this->configurator->rendering->engine instanceof PHP
		 && isset($this->configurator->rendering->engine->lastFilepath))
		{
			$className = \get_class($renderer);
			$filepath  = \realpath($this->configurator->rendering->engine->lastFilepath);
			$php[] = '		if (!class_exists(' . \var_export($className, \true) . ', false)';
			$php[] = '		 && file_exists(' . \var_export($filepath, \true) . '))';
			$php[] = '		{';
			$php[] = '			include ' . \var_export($filepath, \true) . ';';
			$php[] = '		}';
			$php[] = '';
		}
		if (isset($options['rendererSetup']))
		{
			$php[] = '		$renderer = ' . $this->exportObject($renderer) . ';';
			$php[] = '		' . $this->exportCallback($namespace, $options['rendererSetup'], '$renderer') . ';';
			$php[] = '';
			$php[] = '		return $renderer;';
		}
		else
			$php[] = '		return ' . $this->exportObject($renderer) . ';';
		$php[] = '	}';
		$php[] = '}';
		return \implode("\n", $php);
	}
	protected function exportCallback($namespace, $callback, $argument)
	{
		if (\is_array($callback) && \is_string($callback[0]))
			$callback = $callback[0] . '::' . $callback[1];
		if (!\is_string($callback))
			return 'call_user_func(' . \var_export($callback, \true) . ', ' . $argument . ')';
		if ($callback[0] !== '\\')
			$callback = '\\' . $callback;
		if (\substr($callback, 0, 2 + \strlen($namespace)) === '\\' . $namespace . '\\')
			$callback = \substr($callback, 2 + \strlen($namespace));
		return $callback . '(' . $argument . ')';
	}
	protected function exportObject($obj)
	{
		$str = \call_user_func($this->serializer, $obj);
		$str = \var_export($str, \true);
		return $this->unserializer . '(' . $str . ')';
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator;
interface ConfigProvider
{
	public function asConfig();
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator;
interface FilterableConfigValue
{
	public function filterConfig($target);
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Helpers;
use DOMAttr;
use RuntimeException;
abstract class AVTHelper
{
	public static function parse($attrValue)
	{
		$tokens  = array();
		$attrLen = \strlen($attrValue);
		$pos = 0;
		while ($pos < $attrLen)
		{
			if ($attrValue[$pos] === '{')
			{
				if (\substr($attrValue, $pos, 2) === '{{')
				{
					$tokens[] = array('literal', '{');
					$pos += 2;
					continue;
				}
				++$pos;
				$expr = '';
				while ($pos < $attrLen)
				{
					$spn = \strcspn($attrValue, '\'"}', $pos);
					if ($spn)
					{
						$expr .= \substr($attrValue, $pos, $spn);
						$pos += $spn;
					}
					if ($pos >= $attrLen)
						throw new RuntimeException('Unterminated XPath expression');
					$c = $attrValue[$pos];
					++$pos;
					if ($c === '}')
						break;
					$quotePos = \strpos($attrValue, $c, $pos);
					if ($quotePos === \false)
						throw new RuntimeException('Unterminated XPath expression');
					$expr .= $c . \substr($attrValue, $pos, $quotePos + 1 - $pos);
					$pos = 1 + $quotePos;
				}
				$tokens[] = array('expression', $expr);
			}
			$spn = \strcspn($attrValue, '{', $pos);
			if ($spn)
			{
				$str = \substr($attrValue, $pos, $spn);
				$str = \str_replace('}}', '}', $str);
				$tokens[] = array('literal', $str);
				$pos += $spn;
			}
		}
		return $tokens;
	}
	public static function replace(DOMAttr $attribute, $callback)
	{
		$tokens = self::parse($attribute->value);
		foreach ($tokens as $k => $token)
			$tokens[$k] = $callback($token);
		$attribute->value = \htmlspecialchars(self::serialize($tokens), \ENT_NOQUOTES, 'UTF-8');
	}
	public static function serialize(array $tokens)
	{
		$attrValue = '';
		foreach ($tokens as $token)
			if ($token[0] === 'literal')
				$attrValue .= \preg_replace('([{}])', '$0$0', $token[1]);
			elseif ($token[0] === 'expression')
				$attrValue .= '{' . $token[1] . '}';
			else
				throw new RuntimeException('Unknown token type');
		return $attrValue;
	}
	public static function toXSL($attrValue)
	{
		$xsl = '';
		foreach (self::parse($attrValue) as $_f6b3b659)
		{
			list($type, $content) = $_f6b3b659;
			if ($type === 'literal')
				$xsl .= \htmlspecialchars($content, \ENT_NOQUOTES, 'UTF-8');
			else
				$xsl .= '<xsl:value-of select="' . \htmlspecialchars($content, \ENT_COMPAT, 'UTF-8') . '"/>';
		}
		return $xsl;
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Helpers;
class CharacterClassBuilder
{
	protected $chars;
	public $delimiter = '/';
	protected $ranges;
	public function fromList(array $chars)
	{
		$this->chars = $chars;
		$this->unescapeLiterals();
		\sort($this->chars);
		$this->storeRanges();
		$this->reorderDash();
		$this->fixCaret();
		$this->escapeSpecialChars();
		return $this->buildCharacterClass();
	}
	protected function buildCharacterClass()
	{
		$str = '[';
		foreach ($this->ranges as $_b7914274)
		{
			list($start, $end) = $_b7914274;
			if ($end > $start + 2)
				$str .= $this->chars[$start] . '-' . $this->chars[$end];
			else
				$str .= \implode('', \array_slice($this->chars, $start, $end + 1 - $start));
		}
		$str .= ']';
		return $str;
	}
	protected function escapeSpecialChars()
	{
		$specialChars = array('\\', ']', $this->delimiter);
		foreach (\array_intersect($this->chars, $specialChars) as $k => $v)
			$this->chars[$k] = '\\' . $v;
	}
	protected function fixCaret()
	{
		$k = \array_search('^', $this->chars, \true);
		if ($this->ranges[0][0] !== $k)
			return;
		if (isset($this->ranges[1]))
		{
			$range           = $this->ranges[0];
			$this->ranges[0] = $this->ranges[1];
			$this->ranges[1] = $range;
		}
		else
			$this->chars[$k] = '\\^';
	}
	protected function reorderDash()
	{
		$dashIndex = \array_search('-', $this->chars, \true);
		if ($dashIndex === \false)
			return;
		$k = \array_search(array($dashIndex, $dashIndex), $this->ranges, \true);
		if ($k > 0)
		{
			unset($this->ranges[$k]);
			\array_unshift($this->ranges, array($dashIndex, $dashIndex));
		}
		$commaIndex = \array_search(',', $this->chars);
		$range      = array($commaIndex, $dashIndex);
		$k          = \array_search($range, $this->ranges, \true);
		if ($k !== \false)
		{
			$this->ranges[$k] = array($commaIndex, $commaIndex);
			\array_unshift($this->ranges, array($dashIndex, $dashIndex));
		}
	}
	protected function storeRanges()
	{
		$values = array();
		foreach ($this->chars as $char)
			if (\strlen($char) === 1)
				$values[] = \ord($char);
			else
				$values[] = \false;
		$i = \count($values) - 1;
		$ranges = array();
		while ($i >= 0)
		{
			$start = $i;
			$end   = $i;
			while ($start > 0 && $values[$start - 1] === $values[$end] - ($end + 1 - $start))
				--$start;
			$ranges[] = array($start, $end);
			$i = $start - 1;
		}
		$this->ranges = \array_reverse($ranges);
	}
	protected function unescapeLiterals()
	{
		foreach ($this->chars as $k => $char)
			if ($char[0] === '\\' && \preg_match('(^\\\\[^a-z]$)Di', $char))
				$this->chars[$k] = \substr($char, 1);
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Helpers;
use RuntimeException;
use Traversable;
use s9e\TextFormatter\Configurator\ConfigProvider;
use s9e\TextFormatter\Configurator\FilterableConfigValue;
abstract class ConfigHelper
{
	public static function filterConfig(array $config, $target = 'PHP')
	{
		$filteredConfig = array();
		foreach ($config as $name => $value)
		{
			if ($value instanceof FilterableConfigValue)
			{
				$value = $value->filterConfig($target);
				if (!isset($value))
					continue;
			}
			if (\is_array($value))
				$value = self::filterConfig($value, $target);
			$filteredConfig[$name] = $value;
		}
		return $filteredConfig;
	}
	public static function generateQuickMatchFromList(array $strings)
	{
		foreach ($strings as $string)
		{
			$stringLen  = \strlen($string);
			$substrings = array();
			for ($len = $stringLen; $len; --$len)
			{
				$pos = $stringLen - $len;
				do
				{
					$substrings[\substr($string, $pos, $len)] = 1;
				}
				while (--$pos >= 0);
			}
			if (isset($goodStrings))
			{
				$goodStrings = \array_intersect_key($goodStrings, $substrings);
				if (empty($goodStrings))
					break;
			}
			else
				$goodStrings = $substrings;
		}
		if (empty($goodStrings))
			return \false;
		return \strval(\key($goodStrings));
	}
	public static function optimizeArray(array &$config, array &$cache = array())
	{
		foreach ($config as $k => &$v)
		{
			if (!\is_array($v))
				continue;
			self::optimizeArray($v, $cache);
			$cacheKey = \serialize($v);
			if (!isset($cache[$cacheKey]))
				$cache[$cacheKey] = $v;
			$config[$k] =& $cache[$cacheKey];
		}
		unset($v);
	}
	public static function toArray($value, $keepEmpty = \false, $keepNull = \false)
	{
		$array = array();
		foreach ($value as $k => $v)
		{
			if ($v instanceof ConfigProvider)
				$v = $v->asConfig();
			elseif ($v instanceof Traversable || \is_array($v))
				$v = self::toArray($v, $keepEmpty, $keepNull);
			elseif (\is_scalar($v) || \is_null($v))
				;
			else
			{
				$type = (\is_object($v))
				      ? 'an instance of ' . \get_class($v)
				      : 'a ' . \gettype($v);
				throw new RuntimeException('Cannot convert ' . $type . ' to array');
			}
			if (!isset($v) && !$keepNull)
				continue;
			if (!$keepEmpty && $v === array())
				continue;
			$array[$k] = $v;
		}
		return $array;
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Helpers;
use RuntimeException;
abstract class RegexpBuilder
{
	protected static $characterClassBuilder;
	public static function fromList(array $words, array $options = array())
	{
		if (empty($words))
			return '';
		$options += array(
			'delimiter'       => '/',
			'caseInsensitive' => \false,
			'specialChars'    => array(),
			'unicode'         => \true,
			'useLookahead'    => \false
		);
		if ($options['caseInsensitive'])
		{
			foreach ($words as &$word)
				$word = \strtr(
					$word,
					'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
					'abcdefghijklmnopqrstuvwxyz'
				);
			unset($word);
		}
		$words = \array_unique($words);
		\sort($words);
		$initials = array();
		$esc  = $options['specialChars'];
		$esc += array($options['delimiter'] => '\\' . $options['delimiter']);
		$esc += array(
			'!' => '!',
			'-' => '-',
			':' => ':',
			'<' => '<',
			'=' => '=',
			'>' => '>',
			'}' => '}'
		);
		$splitWords = array();
		foreach ($words as $word)
		{
			$regexp = ($options['unicode']) ? '(.)us' : '(.)s';
			if (\preg_match_all($regexp, $word, $matches) === \false || ($options['unicode'] && !\preg_match('/^(?:[[:ascii:]]|[\xC0-\xDF][\x80-\xBF]|[\xE0-\xEF][\x80-\xBF]{2}|[\xF0-\xF7][\x80-\xBF]{3})*$/D', $word)))
				throw new RuntimeException("Invalid UTF-8 string '" . $word . "'");
			$splitWord = array();
			foreach ($matches[0] as $pos => $c)
			{
				if (!isset($esc[$c]))
					$esc[$c] = \preg_quote($c);
				if ($pos === 0)
					$initials[] = $esc[$c];
				$splitWord[] = $esc[$c];
			}
			$splitWords[] = $splitWord;
		}
		self::$characterClassBuilder            = new CharacterClassBuilder;
		self::$characterClassBuilder->delimiter = $options['delimiter'];
		$regexp = self::assemble(array(self::mergeChains($splitWords)));
		if ($options['useLookahead']
		 && \count($initials) > 1
		 && $regexp[0] !== '[')
		{
			$useLookahead = \true;
			foreach ($initials as $initial)
				if (!self::canBeUsedInCharacterClass($initial))
				{
					$useLookahead = \false;
					break;
				}
			if ($useLookahead)
				$regexp = '(?=' . self::generateCharacterClass($initials) . ')' . $regexp;
		}
		return $regexp;
	}
	protected static function mergeChains(array $chains)
	{
		if (!isset($chains[1]))
			return $chains[0];
		$mergedChain = self::removeLongestCommonPrefix($chains);
		if (!isset($chains[0][0])
		 && !\array_filter($chains))
			return $mergedChain;
		$suffix = self::removeLongestCommonSuffix($chains);
		if (isset($chains[1]))
		{
			self::optimizeDotChains($chains);
			self::optimizeCatchallChains($chains);
		}
		$endOfChain = \false;
		$remerge = \false;
		$groups = array();
		foreach ($chains as $chain)
		{
			if (!isset($chain[0]))
			{
				$endOfChain = \true;
				continue;
			}
			$head = $chain[0];
			if (isset($groups[$head]))
				$remerge = \true;
			$groups[$head][] = $chain;
		}
		$characterClass = array();
		foreach ($groups as $head => $groupChains)
		{
			$head = (string) $head;
			if ($groupChains === array(array($head))
			 && self::canBeUsedInCharacterClass($head))
				$characterClass[$head] = $head;
		}
		\sort($characterClass);
		if (isset($characterClass[1]))
		{
			foreach ($characterClass as $char)
				unset($groups[$char]);
			$head = self::generateCharacterClass($characterClass);
			$groups[$head][] = array($head);
			$groups = array($head => $groups[$head])
			        + $groups;
		}
		if ($remerge)
		{
			$mergedChains = array();
			foreach ($groups as $head => $groupChains)
				$mergedChains[] = self::mergeChains($groupChains);
			self::mergeTails($mergedChains);
			$regexp = \implode('', self::mergeChains($mergedChains));
			if ($endOfChain)
				$regexp = self::makeRegexpOptional($regexp);
			$mergedChain[] = $regexp;
		}
		else
		{
			self::mergeTails($chains);
			$mergedChain[] = self::assemble($chains);
		}
		foreach ($suffix as $atom)
			$mergedChain[] = $atom;
		return $mergedChain;
	}
	protected static function mergeTails(array &$chains)
	{
		self::mergeTailsCC($chains);
		self::mergeTailsAltern($chains);
		$chains = \array_values($chains);
	}
	protected static function mergeTailsCC(array &$chains)
	{
		$groups = array();
		foreach ($chains as $k => $chain)
			if (isset($chain[1])
			 && !isset($chain[2])
			 && self::canBeUsedInCharacterClass($chain[0]))
				$groups[$chain[1]][$k] = $chain;
		foreach ($groups as $groupChains)
		{
			if (\count($groupChains) < 2)
				continue;
			$chains = \array_diff_key($chains, $groupChains);
			$chains[] = self::mergeChains(\array_values($groupChains));
		}
	}
	protected static function mergeTailsAltern(array &$chains)
	{
		$groups = array();
		foreach ($chains as $k => $chain)
			if (!empty($chain))
			{
				$tail = \array_slice($chain, -1);
				$groups[$tail[0]][$k] = $chain;
			}
		foreach ($groups as $tail => $groupChains)
		{
			if (\count($groupChains) < 2)
				continue;
			$mergedChain = self::mergeChains(\array_values($groupChains));
			$oldLen = 0;
			foreach ($groupChains as $groupChain)
				$oldLen += \array_sum(\array_map('strlen', $groupChain));
			if ($oldLen <= \array_sum(\array_map('strlen', $mergedChain)))
				continue;
			$chains = \array_diff_key($chains, $groupChains);
			$chains[] = $mergedChain;
		}
	}
	protected static function removeLongestCommonPrefix(array &$chains)
	{
		$pLen = 0;
		while (1)
		{
			$c = \null;
			foreach ($chains as $chain)
			{
				if (!isset($chain[$pLen]))
					break 2;
				if (!isset($c))
				{
					$c = $chain[$pLen];
					continue;
				}
				if ($chain[$pLen] !== $c)
					break 2;
			}
			++$pLen;
		}
		if (!$pLen)
			return array();
		$prefix = \array_slice($chains[0], 0, $pLen);
		foreach ($chains as &$chain)
			$chain = \array_slice($chain, $pLen);
		unset($chain);
		return $prefix;
	}
	protected static function removeLongestCommonSuffix(array &$chains)
	{
		$chainsLen = \array_map('count', $chains);
		$maxLen = \min($chainsLen);
		if (\max($chainsLen) === $maxLen)
			--$maxLen;
		$sLen = 0;
		while ($sLen < $maxLen)
		{
			$c = \null;
			foreach ($chains as $k => $chain)
			{
				$pos = $chainsLen[$k] - ($sLen + 1);
				if (!isset($c))
				{
					$c = $chain[$pos];
					continue;
				}
				if ($chain[$pos] !== $c)
					break 2;
			}
			++$sLen;
		}
		if (!$sLen)
			return array();
		$suffix = \array_slice($chains[0], -$sLen);
		foreach ($chains as &$chain)
			$chain = \array_slice($chain, 0, -$sLen);
		unset($chain);
		return $suffix;
	}
	protected static function assemble(array $chains)
	{
		$endOfChain = \false;
		$regexps        = array();
		$characterClass = array();
		foreach ($chains as $chain)
		{
			if (empty($chain))
			{
				$endOfChain = \true;
				continue;
			}
			if (!isset($chain[1])
			 && self::canBeUsedInCharacterClass($chain[0]))
				$characterClass[$chain[0]] = $chain[0];
			else
				$regexps[] = \implode('', $chain);
		}
		if (!empty($characterClass))
		{
			\sort($characterClass);
			$regexp = (isset($characterClass[1]))
					? self::generateCharacterClass($characterClass)
					: $characterClass[0];
			\array_unshift($regexps, $regexp);
		}
		if (empty($regexps))
			return '';
		if (isset($regexps[1]))
		{
			$regexp = \implode('|', $regexps);
			$regexp = ((self::canUseAtomicGrouping($regexp)) ? '(?>' : '(?:') . $regexp . ')';
		}
		else
			$regexp = $regexps[0];
		if ($endOfChain)
			$regexp = self::makeRegexpOptional($regexp);
		return $regexp;
	}
	protected static function makeRegexpOptional($regexp)
	{
		if (\preg_match('#^\\.\\+\\??$#', $regexp))
			return \str_replace('+', '*', $regexp);
		if (\preg_match('#^(\\\\?.)((?:\\1\\?)+)$#Du', $regexp, $m))
			return $m[1] . '?' . $m[2];
		if (\preg_match('#^(?:[$^]|\\\\[bBAZzGQEK])$#', $regexp))
			return '';
		if (\preg_match('#^\\\\?.$#Dus', $regexp))
			$isAtomic = \true;
		elseif (\preg_match('#^[^[(].#s', $regexp))
			$isAtomic = \false;
		else
		{
			$def    = RegexpParser::parse('#' . $regexp . '#');
			$tokens = $def['tokens'];
			switch (\count($tokens))
			{
				case 1:
					$startPos = $tokens[0]['pos'];
					$len      = $tokens[0]['len'];
					$isAtomic = (bool) ($startPos === 0 && $len === \strlen($regexp));
					if ($isAtomic && $tokens[0]['type'] === 'characterClass')
					{
						$regexp = \rtrim($regexp, '+*?');
						if (!empty($tokens[0]['quantifiers']) && $tokens[0]['quantifiers'] !== '?')
							$regexp .= '*';
					}
					break;
				case 2:
					if ($tokens[0]['type'] === 'nonCapturingSubpatternStart'
					 && $tokens[1]['type'] === 'nonCapturingSubpatternEnd')
					{
						$startPos = $tokens[0]['pos'];
						$len      = $tokens[1]['pos'] + $tokens[1]['len'];
						$isAtomic = (bool) ($startPos === 0 && $len === \strlen($regexp));
						break;
					}
					default:
					$isAtomic = \false;
			}
		}
		if (!$isAtomic)
			$regexp = ((self::canUseAtomicGrouping($regexp)) ? '(?>' : '(?:') . $regexp . ')';
		$regexp .= '?';
		return $regexp;
	}
	protected static function generateCharacterClass(array $chars)
	{
		return self::$characterClassBuilder->fromList($chars);
	}
	protected static function canBeUsedInCharacterClass($char)
	{
		if (\preg_match('#^\\\\[aefnrtdDhHsSvVwW]$#D', $char))
			return \true;
		if (\preg_match('#^\\\\[^A-Za-z0-9]$#Dus', $char))
			return \true;
		if (\preg_match('#..#Dus', $char))
			return \false;
		if (\preg_quote($char) !== $char
		 && !\preg_match('#^[-!:<=>}]$#D', $char))
			return \false;
		return \true;
	}
	protected static function optimizeDotChains(array &$chains)
	{
		$validAtoms = array(
			'\\d' => 1, '\\D' => 1, '\\h' => 1, '\\H' => 1,
			'\\s' => 1, '\\S' => 1, '\\v' => 1, '\\V' => 1,
			'\\w' => 1, '\\W' => 1,
			'\\^' => 1, '\\$' => 1, '\\.' => 1, '\\?' => 1,
			'\\[' => 1, '\\]' => 1, '\\(' => 1, '\\)' => 1,
			'\\+' => 1, '\\*' => 1, '\\\\' => 1
		);
		do
		{
			$hasMoreDots = \false;
			foreach ($chains as $k1 => $dotChain)
			{
				$dotKeys = \array_keys($dotChain, '.?', \true);
				if (!empty($dotKeys))
				{
					$dotChain[$dotKeys[0]] = '.';
					$chains[$k1] = $dotChain;
					\array_splice($dotChain, $dotKeys[0], 1);
					$chains[] = $dotChain;
					if (isset($dotKeys[1]))
						$hasMoreDots = \true;
				}
			}
		}
		while ($hasMoreDots);
		foreach ($chains as $k1 => $dotChain)
		{
			$dotKeys = \array_keys($dotChain, '.', \true);
			if (empty($dotKeys))
				continue;
			foreach ($chains as $k2 => $tmpChain)
			{
				if ($k2 === $k1)
					continue;
				foreach ($dotKeys as $dotKey)
				{
					if (!isset($tmpChain[$dotKey]))
						continue 2;
					if (!\preg_match('#^.$#Du', \preg_quote($tmpChain[$dotKey]))
					 && !isset($validAtoms[$tmpChain[$dotKey]]))
						continue 2;
					$tmpChain[$dotKey] = '.';
				}
				if ($tmpChain === $dotChain)
					unset($chains[$k2]);
			}
		}
	}
	protected static function optimizeCatchallChains(array &$chains)
	{
		$precedence = array(
			'.*'  => 3,
			'.*?' => 2,
			'.+'  => 1,
			'.+?' => 0
		);
		$tails = array();
		foreach ($chains as $k => $chain)
		{
			if (!isset($chain[0]))
				continue;
			$head = $chain[0];
			if (!isset($precedence[$head]))
				continue;
			$tail = \implode('', \array_slice($chain, 1));
			if (!isset($tails[$tail])
			 || $precedence[$head] > $tails[$tail]['precedence'])
				$tails[$tail] = array(
					'key'        => $k,
					'precedence' => $precedence[$head]
				);
		}
		$catchallChains = array();
		foreach ($tails as $tail => $info)
			$catchallChains[$info['key']] = $chains[$info['key']];
		foreach ($catchallChains as $k1 => $catchallChain)
		{
			$headExpr = $catchallChain[0];
			$tailExpr = \false;
			$match    = \array_slice($catchallChain, 1);
			if (isset($catchallChain[1])
			 && isset($precedence[\end($catchallChain)]))
				$tailExpr = \array_pop($match);
			$matchCnt = \count($match);
			foreach ($chains as $k2 => $chain)
			{
				if ($k2 === $k1)
					continue;
				$start = 0;
				$end = \count($chain);
				if ($headExpr[1] === '+')
				{
					$found = \false;
					foreach ($chain as $start => $atom)
						if (self::matchesAtLeastOneCharacter($atom))
						{
							$found = \true;
							break;
						}
					if (!$found)
						continue;
				}
				if ($tailExpr === \false)
					$end = $start;
				else
				{
					if ($tailExpr[1] === '+')
					{
						$found = \false;
						while (--$end > $start)
							if (self::matchesAtLeastOneCharacter($chain[$end]))
							{
								$found = \true;
								break;
							}
						if (!$found)
							continue;
					}
					$end -= $matchCnt;
				}
				while ($start <= $end)
				{
					if (\array_slice($chain, $start, $matchCnt) === $match)
					{
						unset($chains[$k2]);
						break;
					}
					++$start;
				}
			}
		}
	}
	protected static function matchesAtLeastOneCharacter($expr)
	{
		if (\preg_match('#^[$*?^]$#', $expr))
			return \false;
		if (\preg_match('#^.$#u', $expr))
			return \true;
		if (\preg_match('#^.\\+#u', $expr))
			return \true;
		if (\preg_match('#^\\\\[^bBAZzGQEK1-9](?![*?])#', $expr))
			return \true;
		return \false;
	}
	protected static function canUseAtomicGrouping($expr)
	{
		if (\preg_match('#(?<!\\\\)(?>\\\\\\\\)*\\.#', $expr))
			return \false;
		if (\preg_match('#(?<!\\\\)(?>\\\\\\\\)*[+*]#', $expr))
			return \false;
		if (\preg_match('#(?<!\\\\)(?>\\\\\\\\)*\\(?(?<!\\()\\?#', $expr))
			return \false;
		if (\preg_match('#(?<!\\\\)(?>\\\\\\\\)*\\\\[a-z0-9]#', $expr))
			return \false;
		return \true;
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Helpers;
use s9e\TextFormatter\Configurator\Collections\Ruleset;
use s9e\TextFormatter\Configurator\Collections\TagCollection;
abstract class RulesHelper
{
	public static function getBitfields(TagCollection $tags, Ruleset $rootRules)
	{
		$rules = array('*root*' => \iterator_to_array($rootRules));
		foreach ($tags as $tagName => $tag)
			$rules[$tagName] = \iterator_to_array($tag->rules);
		$matrix = self::unrollRules($rules);
		self::pruneMatrix($matrix);
		$groupedTags = array();
		foreach (\array_keys($matrix) as $tagName)
		{
			if ($tagName === '*root*')
				continue;
			$k = '';
			foreach ($matrix as $tagMatrix)
			{
				$k .= $tagMatrix['allowedChildren'][$tagName];
				$k .= $tagMatrix['allowedDescendants'][$tagName];
			}
			$groupedTags[$k][] = $tagName;
		}
		$bitTag     = array();
		$bitNumber  = 0;
		$tagsConfig = array();
		foreach ($groupedTags as $tagNames)
		{
			foreach ($tagNames as $tagName)
			{
				$tagsConfig[$tagName]['bitNumber'] = $bitNumber;
				$bitTag[$bitNumber] = $tagName;
			}
			++$bitNumber;
		}
		foreach ($matrix as $tagName => $tagMatrix)
		{
			$allowedChildren    = '';
			$allowedDescendants = '';
			foreach ($bitTag as $targetName)
			{
				$allowedChildren    .= $tagMatrix['allowedChildren'][$targetName];
				$allowedDescendants .= $tagMatrix['allowedDescendants'][$targetName];
			}
			$tagsConfig[$tagName]['allowed'] = self::pack($allowedChildren, $allowedDescendants);
		}
		$return = array(
			'root' => $tagsConfig['*root*'],
			'tags' => $tagsConfig
		);
		unset($return['tags']['*root*']);
		return $return;
	}
	protected static function initMatrix(array $rules)
	{
		$matrix   = array();
		$tagNames = \array_keys($rules);
		foreach ($rules as $tagName => $tagRules)
		{
			if ($tagRules['defaultDescendantRule'] === 'allow')
			{
				$childValue      = (int) ($tagRules['defaultChildRule'] === 'allow');
				$descendantValue = 1;
			}
			else
			{
				$childValue      = 0;
				$descendantValue = 0;
			}
			$matrix[$tagName]['allowedChildren']    = \array_fill_keys($tagNames, $childValue);
			$matrix[$tagName]['allowedDescendants'] = \array_fill_keys($tagNames, $descendantValue);
		}
		return $matrix;
	}
	protected static function applyTargetedRule(array &$matrix, $rules, $ruleName, $key, $value)
	{
		foreach ($rules as $tagName => $tagRules)
		{
			if (!isset($tagRules[$ruleName]))
				continue;
			foreach ($tagRules[$ruleName] as $targetName)
				$matrix[$tagName][$key][$targetName] = $value;
		}
	}
	protected static function unrollRules(array $rules)
	{
		$matrix = self::initMatrix($rules);
		$tagNames = \array_keys($rules);
		foreach ($rules as $tagName => $tagRules)
		{
			if (!empty($tagRules['ignoreTags']))
				$rules[$tagName]['denyDescendant'] = $tagNames;
			if (!empty($tagRules['requireParent']))
			{
				$denyParents = \array_diff($tagNames, $tagRules['requireParent']);
				foreach ($denyParents as $parentName)
					$rules[$parentName]['denyChild'][] = $tagName;
			}
		}
		self::applyTargetedRule($matrix, $rules, 'allowChild',      'allowedChildren',    1);
		self::applyTargetedRule($matrix, $rules, 'allowDescendant', 'allowedChildren',    1);
		self::applyTargetedRule($matrix, $rules, 'allowDescendant', 'allowedDescendants', 1);
		self::applyTargetedRule($matrix, $rules, 'denyChild',      'allowedChildren',    0);
		self::applyTargetedRule($matrix, $rules, 'denyDescendant', 'allowedChildren',    0);
		self::applyTargetedRule($matrix, $rules, 'denyDescendant', 'allowedDescendants', 0);
		return $matrix;
	}
	protected static function pruneMatrix(array &$matrix)
	{
		$usableTags = array('*root*' => 1);
		$parentTags = $usableTags;
		do
		{
			$nextTags = array();
			foreach (\array_keys($parentTags) as $tagName)
				$nextTags += \array_filter($matrix[$tagName]['allowedChildren']);
			$parentTags  = \array_diff_key($nextTags, $usableTags);
			$parentTags  = \array_intersect_key($parentTags, $matrix);
			$usableTags += $parentTags;
		}
		while (!empty($parentTags));
		$matrix = \array_intersect_key($matrix, $usableTags);
		unset($usableTags['*root*']);
		foreach ($matrix as $tagName => &$tagMatrix)
		{
			$tagMatrix['allowedChildren']
				= \array_intersect_key($tagMatrix['allowedChildren'], $usableTags);
			$tagMatrix['allowedDescendants']
				= \array_intersect_key($tagMatrix['allowedDescendants'], $usableTags);
		}
		unset($tagMatrix);
	}
	protected static function pack($allowedChildren, $allowedDescendants)
	{
		$allowedChildren    = \str_split($allowedChildren,    8);
		$allowedDescendants = \str_split($allowedDescendants, 8);
		$allowed = array();
		foreach (\array_keys($allowedChildren) as $k)
			$allowed[] = \bindec(\sprintf(
				'%1$08s%2$08s',
				\strrev($allowedDescendants[$k]),
				\strrev($allowedChildren[$k])
			));
		return $allowed;
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Helpers;
use DOMDocument;
use DOMElement;
use DOMXPath;
class TemplateForensics
{
	protected $allowChildBitfield = "\0";
	protected $allowsChildElements = \true;
	protected $allowsText = \true;
	protected $contentBitfield = "\0";
	protected $denyDescendantBitfield = "\0";
	protected $dom;
	protected $hasElements = \false;
	protected $hasRootText = \false;
	protected $isBlock = \false;
	protected $isEmpty = \true;
	protected $isFormattingElement = \false;
	protected $isPassthrough = \false;
	protected $isTransparent = \false;
	protected $isVoid = \true;
	protected $leafNodes = array();
	protected $preservesNewLines = \false;
	protected $rootBitfields = array();
	protected $rootNodes = array();
	protected $xpath;
	public function __construct($template)
	{
		$this->dom   = TemplateHelper::loadTemplate($template);
		$this->xpath = new DOMXPath($this->dom);
		$this->analyseRootNodes();
		$this->analyseBranches();
		$this->analyseContent();
	}
	public function allowsChild(self $child)
	{
		if (!$this->allowsDescendant($child))
			return \false;
		foreach ($child->rootBitfields as $rootBitfield)
			if (!self::match($rootBitfield, $this->allowChildBitfield))
				return \false;
		if (!$this->allowsText && $child->hasRootText)
			return \false;
		return \true;
	}
	public function allowsDescendant(self $descendant)
	{
		if (self::match($descendant->contentBitfield, $this->denyDescendantBitfield))
			return \false;
		if (!$this->allowsChildElements && $descendant->hasElements)
			return \false;
		return \true;
	}
	public function allowsChildElements()
	{
		return $this->allowsChildElements;
	}
	public function allowsText()
	{
		return $this->allowsText;
	}
	public function closesParent(self $parent)
	{
		foreach ($this->rootNodes as $rootName)
		{
			if (empty(self::$htmlElements[$rootName]['cp']))
				continue;
			foreach ($parent->leafNodes as $leafName)
				if (\in_array($leafName, self::$htmlElements[$rootName]['cp'], \true))
					return \true;
		}
		return \false;
	}
	public function getDOM()
	{
		return $this->dom;
	}
	public function isBlock()
	{
		return $this->isBlock;
	}
	public function isFormattingElement()
	{
		return $this->isFormattingElement;
	}
	public function isEmpty()
	{
		return $this->isEmpty;
	}
	public function isPassthrough()
	{
		return $this->isPassthrough;
	}
	public function isTransparent()
	{
		return $this->isTransparent;
	}
	public function isVoid()
	{
		return $this->isVoid;
	}
	public function preservesNewLines()
	{
		return $this->preservesNewLines;
	}
	protected function analyseContent()
	{
		$query = '//*[namespace-uri() != "http://www.w3.org/1999/XSL/Transform"]';
		foreach ($this->xpath->query($query) as $node)
		{
			$this->contentBitfield |= $this->getBitfield($node->localName, 'c', $node);
			$this->hasElements = \true;
		}
		$this->isPassthrough = (bool) $this->xpath->evaluate('count(//xsl:apply-templates)');
	}
	protected function analyseRootNodes()
	{
		$query = '//*[namespace-uri() != "http://www.w3.org/1999/XSL/Transform"][not(ancestor::*[namespace-uri() != "http://www.w3.org/1999/XSL/Transform"])]';
		foreach ($this->xpath->query($query) as $node)
		{
			$elName = $node->localName;
			$this->rootNodes[] = $elName;
			if (!isset(self::$htmlElements[$elName]))
				$elName = 'span';
			if ($this->elementIsBlock($elName, $node))
				$this->isBlock = \true;
			$this->rootBitfields[] = $this->getBitfield($elName, 'c', $node);
		}
		$predicate = '[not(ancestor::*[namespace-uri() != "http://www.w3.org/1999/XSL/Transform"])]';
		$predicate .= '[not(ancestor::xsl:attribute | ancestor::xsl:comment | ancestor::xsl:variable)]';
		$query = '//text()[normalize-space() != ""]' . $predicate
		       . '|//xsl:text[normalize-space() != ""]' . $predicate
		       . '|//xsl:value-of' . $predicate;
		if ($this->evaluate($query, $this->dom->documentElement))
			$this->hasRootText = \true;
	}
	protected function analyseBranches()
	{
		$branchBitfields = array();
		$isFormattingElement = \true;
		$this->isTransparent = \true;
		foreach ($this->getXSLElements('apply-templates') as $applyTemplates)
		{
			$nodes = $this->xpath->query(
				'ancestor::*[namespace-uri() != "http://www.w3.org/1999/XSL/Transform"]',
				$applyTemplates
			);
			$allowsChildElements = \true;
			$allowsText = \true;
			$branchBitfield = self::$htmlElements['div']['ac'];
			$isEmpty = \false;
			$isVoid = \false;
			$leafNode = \null;
			$preservesNewLines = \false;
			foreach ($nodes as $node)
			{
				$elName = $leafNode = $node->localName;
				if (!isset(self::$htmlElements[$elName]))
					$elName = 'span';
				if ($this->hasProperty($elName, 'v', $node))
					$isVoid = \true;
				if ($this->hasProperty($elName, 'e', $node))
					$isEmpty = \true;
				if (!$this->hasProperty($elName, 't', $node))
				{
					$branchBitfield = "\0";
					$this->isTransparent = \false;
				}
				if (!$this->hasProperty($elName, 'fe', $node)
				 && !$this->isFormattingSpan($node))
					$isFormattingElement = \false;
				$allowsChildElements = !$this->hasProperty($elName, 'to', $node);
				$allowsText = !$this->hasProperty($elName, 'nt', $node);
				$branchBitfield |= $this->getBitfield($elName, 'ac', $node);
				$this->denyDescendantBitfield |= $this->getBitfield($elName, 'dd', $node);
				$style = '';
				if ($this->hasProperty($elName, 'pre', $node))
					$style .= 'white-space:pre;';
				if ($node->hasAttribute('style'))
					$style .= $node->getAttribute('style') . ';';
				$attributes = $this->xpath->query('.//xsl:attribute[@name="style"]', $node);
				foreach ($attributes as $attribute)
					$style .= $attribute->textContent;
				\preg_match_all(
					'/white-space\\s*:\\s*(no|pre)/i',
					\strtolower($style),
					$matches
				);
				foreach ($matches[1] as $match)
					$preservesNewLines = ($match === 'pre');
			}
			$branchBitfields[] = $branchBitfield;
			if (isset($leafNode))
				$this->leafNodes[] = $leafNode;
			if (!$allowsChildElements)
				$this->allowsChildElements = \false;
			if (!$allowsText)
				$this->allowsText = \false;
			if (!$isEmpty)
				$this->isEmpty = \false;
			if (!$isVoid)
				$this->isVoid = \false;
			if ($preservesNewLines)
				$this->preservesNewLines = \true;
		}
		if (empty($branchBitfields))
		{
			$this->allowsChildElements = \false;
			$this->isTransparent       = \false;
		}
		else
		{
			$this->allowChildBitfield = $branchBitfields[0];
			foreach ($branchBitfields as $branchBitfield)
				$this->allowChildBitfield &= $branchBitfield;
			if (!empty($this->leafNodes))
				$this->isFormattingElement = $isFormattingElement;
		}
	}
	protected function elementIsBlock($elName, DOMElement $node)
	{
		$style = $this->getStyle($node);
		if (\preg_match('(\\bdisplay\\s*:\\s*block)i', $style))
			return \true;
		if (\preg_match('(\\bdisplay\\s*:\\s*inline)i', $style))
			return \false;
		return $this->hasProperty($elName, 'b', $node);
	}
	protected function evaluate($query, DOMElement $node)
	{
		return $this->xpath->evaluate('boolean(' . $query . ')', $node);
	}
	protected function getStyle(DOMElement $node)
	{
		$style = $node->getAttribute('style');
		$xpath = new DOMXPath($node->ownerDocument);
		$query = 'xsl:attribute[@name="style"]';
		foreach ($xpath->query($query, $node) as $attribute)
			$style .= ';' . $attribute->textContent;
		return $style;
	}
	protected function getXSLElements($elName)
	{
		return $this->dom->getElementsByTagNameNS('http://www.w3.org/1999/XSL/Transform', $elName);
	}
	protected function isFormattingSpan(DOMElement $node)
	{
		if ($node->nodeName !== 'span')
			return \false;
		if ($node->getAttribute('class') === ''
		 && $node->getAttribute('style') === '')
			return \false;
		foreach ($node->attributes as $attrName => $attribute)
			if ($attrName !== 'class' && $attrName !== 'style')
				return \false;
		return \true;
	}
	protected static $htmlElements = array(
		'a'=>array('c'=>"\17\0\0\0\0\1",'c3'=>'@href','ac'=>"\0",'dd'=>"\10\0\0\0\0\1",'t'=>1,'fe'=>1),
		'abbr'=>array('c'=>"\7",'ac'=>"\4"),
		'address'=>array('c'=>"\3\40",'ac'=>"\1",'dd'=>"\0\45",'b'=>1,'cp'=>array('p')),
		'article'=>array('c'=>"\3\4",'ac'=>"\1",'b'=>1,'cp'=>array('p')),
		'aside'=>array('c'=>"\3\4",'ac'=>"\1",'dd'=>"\0\0\0\0\10",'b'=>1,'cp'=>array('p')),
		'audio'=>array('c'=>"\57",'c3'=>'@controls','c1'=>'@controls','ac'=>"\0\0\0\104",'ac26'=>'not(@src)','dd'=>"\0\0\0\0\0\2",'dd41'=>'@src','t'=>1),
		'b'=>array('c'=>"\7",'ac'=>"\4",'fe'=>1),
		'base'=>array('c'=>"\20",'nt'=>1,'e'=>1,'v'=>1,'b'=>1),
		'bdi'=>array('c'=>"\7",'ac'=>"\4"),
		'bdo'=>array('c'=>"\7",'ac'=>"\4"),
		'blockquote'=>array('c'=>"\203",'ac'=>"\1",'b'=>1,'cp'=>array('p')),
		'body'=>array('c'=>"\200\0\4",'ac'=>"\1",'b'=>1),
		'br'=>array('c'=>"\5",'nt'=>1,'e'=>1,'v'=>1),
		'button'=>array('c'=>"\117",'ac'=>"\4",'dd'=>"\10"),
		'canvas'=>array('c'=>"\47",'ac'=>"\0",'t'=>1),
		'caption'=>array('c'=>"\0\2",'ac'=>"\1",'dd'=>"\0\0\0\200",'b'=>1),
		'cite'=>array('c'=>"\7",'ac'=>"\4"),
		'code'=>array('c'=>"\7",'ac'=>"\4",'fe'=>1),
		'col'=>array('c'=>"\0\0\20",'nt'=>1,'e'=>1,'v'=>1,'b'=>1),
		'colgroup'=>array('c'=>"\0\2",'ac'=>"\0\0\20",'ac20'=>'not(@span)','nt'=>1,'e'=>1,'e0'=>'@span','b'=>1),
		'data'=>array('c'=>"\7",'ac'=>"\4"),
		'datalist'=>array('c'=>"\5",'ac'=>"\4\200\0\10"),
		'dd'=>array('c'=>"\0\0\200",'ac'=>"\1",'b'=>1,'cp'=>array('dd','dt')),
		'del'=>array('c'=>"\5",'ac'=>"\0",'t'=>1),
		'details'=>array('c'=>"\213",'ac'=>"\1\0\0\2",'b'=>1,'cp'=>array('p')),
		'dfn'=>array('c'=>"\7\0\0\0\40",'ac'=>"\4",'dd'=>"\0\0\0\0\40"),
		'div'=>array('c'=>"\3",'ac'=>"\1",'b'=>1,'cp'=>array('p')),
		'dl'=>array('c'=>"\3",'c1'=>'dt and dd','ac'=>"\0\200\200",'nt'=>1,'b'=>1,'cp'=>array('p')),
		'dt'=>array('c'=>"\0\0\200",'ac'=>"\1",'dd'=>"\0\5\0\40",'b'=>1,'cp'=>array('dd','dt')),
		'em'=>array('c'=>"\7",'ac'=>"\4",'fe'=>1),
		'embed'=>array('c'=>"\57",'nt'=>1,'e'=>1,'v'=>1),
		'fieldset'=>array('c'=>"\303",'ac'=>"\1\0\0\20",'b'=>1,'cp'=>array('p')),
		'figcaption'=>array('c'=>"\0\0\0\0\0\4",'ac'=>"\1",'b'=>1,'cp'=>array('p')),
		'figure'=>array('c'=>"\203",'ac'=>"\1\0\0\0\0\4",'b'=>1,'cp'=>array('p')),
		'footer'=>array('c'=>"\3\40",'ac'=>"\1",'dd'=>"\0\0\0\0\10",'b'=>1,'cp'=>array('p')),
		'form'=>array('c'=>"\3\0\0\0\20",'ac'=>"\1",'dd'=>"\0\0\0\0\20",'b'=>1,'cp'=>array('p')),
		'h1'=>array('c'=>"\3\1",'ac'=>"\4",'b'=>1,'cp'=>array('p')),
		'h2'=>array('c'=>"\3\1",'ac'=>"\4",'b'=>1,'cp'=>array('p')),
		'h3'=>array('c'=>"\3\1",'ac'=>"\4",'b'=>1,'cp'=>array('p')),
		'h4'=>array('c'=>"\3\1",'ac'=>"\4",'b'=>1,'cp'=>array('p')),
		'h5'=>array('c'=>"\3\1",'ac'=>"\4",'b'=>1,'cp'=>array('p')),
		'h6'=>array('c'=>"\3\1",'ac'=>"\4",'b'=>1,'cp'=>array('p')),
		'head'=>array('c'=>"\0\0\4",'ac'=>"\20",'nt'=>1,'b'=>1),
		'header'=>array('c'=>"\3\40\0\40",'ac'=>"\1",'dd'=>"\0\0\0\0\10",'b'=>1,'cp'=>array('p')),
		'hr'=>array('c'=>"\1\100",'nt'=>1,'e'=>1,'v'=>1,'b'=>1,'cp'=>array('p')),
		'html'=>array('c'=>"\0",'ac'=>"\0\0\4",'nt'=>1,'b'=>1),
		'i'=>array('c'=>"\7",'ac'=>"\4",'fe'=>1),
		'iframe'=>array('c'=>"\57",'ac'=>"\4"),
		'img'=>array('c'=>"\57\20\10",'c3'=>'@usemap','nt'=>1,'e'=>1,'v'=>1),
		'input'=>array('c'=>"\17\20",'c3'=>'@type!="hidden"','c12'=>'@type!="hidden" or @type="hidden"','c1'=>'@type!="hidden"','nt'=>1,'e'=>1,'v'=>1),
		'ins'=>array('c'=>"\7",'ac'=>"\0",'t'=>1),
		'kbd'=>array('c'=>"\7",'ac'=>"\4"),
		'keygen'=>array('c'=>"\117",'nt'=>1,'e'=>1,'v'=>1),
		'label'=>array('c'=>"\17\20\0\0\4",'ac'=>"\4",'dd'=>"\0\0\1\0\4"),
		'legend'=>array('c'=>"\0\0\0\20",'ac'=>"\4",'b'=>1),
		'li'=>array('c'=>"\0\0\0\0\200",'ac'=>"\1",'b'=>1,'cp'=>array('li')),
		'link'=>array('c'=>"\20",'nt'=>1,'e'=>1,'v'=>1,'b'=>1),
		'main'=>array('c'=>"\3\0\0\0\10",'ac'=>"\1",'b'=>1,'cp'=>array('p')),
		'mark'=>array('c'=>"\7",'ac'=>"\4"),
		'media element'=>array('c'=>"\0\0\0\0\0\2",'nt'=>1,'b'=>1),
		'menu'=>array('c'=>"\1\100",'ac'=>"\0\300",'nt'=>1,'b'=>1,'cp'=>array('p')),
		'menuitem'=>array('c'=>"\0\100",'nt'=>1,'e'=>1,'v'=>1,'b'=>1),
		'meta'=>array('c'=>"\20",'nt'=>1,'e'=>1,'v'=>1,'b'=>1),
		'meter'=>array('c'=>"\7\0\1\0\2",'ac'=>"\4",'dd'=>"\0\0\0\0\2"),
		'nav'=>array('c'=>"\3\4",'ac'=>"\1",'dd'=>"\0\0\0\0\10",'b'=>1,'cp'=>array('p')),
		'noscript'=>array('c'=>"\25",'nt'=>1),
		'object'=>array('c'=>"\147",'ac'=>"\0\0\0\0\1",'t'=>1),
		'ol'=>array('c'=>"\3",'c1'=>'li','ac'=>"\0\200\0\0\200",'nt'=>1,'b'=>1,'cp'=>array('p')),
		'optgroup'=>array('c'=>"\0\0\2",'ac'=>"\0\200\0\10",'nt'=>1,'b'=>1,'cp'=>array('optgroup','option')),
		'option'=>array('c'=>"\0\0\2\10",'b'=>1,'cp'=>array('option')),
		'output'=>array('c'=>"\107",'ac'=>"\4"),
		'p'=>array('c'=>"\3",'ac'=>"\4",'b'=>1,'cp'=>array('p')),
		'param'=>array('c'=>"\0\0\0\0\1",'nt'=>1,'e'=>1,'v'=>1,'b'=>1),
		'picture'=>array('c'=>"\45",'ac'=>"\0\200\10",'nt'=>1),
		'pre'=>array('c'=>"\3",'ac'=>"\4",'pre'=>1,'b'=>1,'cp'=>array('p')),
		'progress'=>array('c'=>"\7\0\1\1",'ac'=>"\4",'dd'=>"\0\0\0\1"),
		'q'=>array('c'=>"\7",'ac'=>"\4"),
		'rb'=>array('c'=>"\0\10",'ac'=>"\4",'b'=>1),
		'rp'=>array('c'=>"\0\10\100",'ac'=>"\4",'b'=>1,'cp'=>array('rp','rt')),
		'rt'=>array('c'=>"\0\10\100",'ac'=>"\4",'b'=>1,'cp'=>array('rp','rt')),
		'rtc'=>array('c'=>"\0\10",'ac'=>"\4\0\100",'b'=>1),
		'ruby'=>array('c'=>"\7",'ac'=>"\4\10"),
		's'=>array('c'=>"\7",'ac'=>"\4",'fe'=>1),
		'samp'=>array('c'=>"\7",'ac'=>"\4"),
		'script'=>array('c'=>"\25\200",'to'=>1),
		'section'=>array('c'=>"\3\4",'ac'=>"\1",'b'=>1,'cp'=>array('p')),
		'select'=>array('c'=>"\117",'ac'=>"\0\200\2",'nt'=>1),
		'small'=>array('c'=>"\7",'ac'=>"\4",'fe'=>1),
		'source'=>array('c'=>"\0\0\10\4",'nt'=>1,'e'=>1,'v'=>1,'b'=>1),
		'span'=>array('c'=>"\7",'ac'=>"\4"),
		'strong'=>array('c'=>"\7",'ac'=>"\4",'fe'=>1),
		'style'=>array('c'=>"\20",'to'=>1,'b'=>1),
		'sub'=>array('c'=>"\7",'ac'=>"\4"),
		'summary'=>array('c'=>"\0\0\0\2",'ac'=>"\4\1",'b'=>1),
		'sup'=>array('c'=>"\7",'ac'=>"\4"),
		'table'=>array('c'=>"\3\0\0\200",'ac'=>"\0\202",'nt'=>1,'b'=>1,'cp'=>array('p')),
		'tbody'=>array('c'=>"\0\2",'ac'=>"\0\200\0\0\100",'nt'=>1,'b'=>1,'cp'=>array('tbody','td','tfoot','th','thead','tr')),
		'td'=>array('c'=>"\200\0\40",'ac'=>"\1",'b'=>1,'cp'=>array('td','th')),
		'template'=>array('c'=>"\25\200\20",'nt'=>1),
		'textarea'=>array('c'=>"\117",'pre'=>1,'to'=>1),
		'tfoot'=>array('c'=>"\0\2",'ac'=>"\0\200\0\0\100",'nt'=>1,'b'=>1,'cp'=>array('tbody','td','th','thead','tr')),
		'th'=>array('c'=>"\0\0\40",'ac'=>"\1",'dd'=>"\0\5\0\40",'b'=>1,'cp'=>array('td','th')),
		'thead'=>array('c'=>"\0\2",'ac'=>"\0\200\0\0\100",'nt'=>1,'b'=>1),
		'time'=>array('c'=>"\7",'ac'=>"\4",'ac2'=>'@datetime'),
		'title'=>array('c'=>"\20",'to'=>1,'b'=>1),
		'tr'=>array('c'=>"\0\2\0\0\100",'ac'=>"\0\200\40",'nt'=>1,'b'=>1,'cp'=>array('td','th','tr')),
		'track'=>array('c'=>"\0\0\0\100",'nt'=>1,'e'=>1,'v'=>1,'b'=>1),
		'u'=>array('c'=>"\7",'ac'=>"\4",'fe'=>1),
		'ul'=>array('c'=>"\3",'c1'=>'li','ac'=>"\0\200\0\0\200",'nt'=>1,'b'=>1,'cp'=>array('p')),
		'var'=>array('c'=>"\7",'ac'=>"\4"),
		'video'=>array('c'=>"\57",'c3'=>'@controls','ac'=>"\0\0\0\104",'ac26'=>'not(@src)','dd'=>"\0\0\0\0\0\2",'dd41'=>'@src','t'=>1),
		'wbr'=>array('c'=>"\5",'nt'=>1,'e'=>1,'v'=>1)
	);
	protected function getBitfield($elName, $k, DOMElement $node)
	{
		if (!isset(self::$htmlElements[$elName][$k]))
			return "\0";
		$bitfield = self::$htmlElements[$elName][$k];
		foreach (\str_split($bitfield, 1) as $byteNumber => $char)
		{
			$byteValue = \ord($char);
			for ($bitNumber = 0; $bitNumber < 8; ++$bitNumber)
			{
				$bitValue = 1 << $bitNumber;
				if (!($byteValue & $bitValue))
					continue;
				$n = $byteNumber * 8 + $bitNumber;
				if (isset(self::$htmlElements[$elName][$k . $n]))
				{
					$xpath = 'boolean(' . self::$htmlElements[$elName][$k . $n] . ')';
					if (!$this->evaluate($xpath, $node))
					{
						$byteValue ^= $bitValue;
						$bitfield[$byteNumber] = \chr($byteValue);
					}
				}
			}
		}
		return $bitfield;
	}
	protected function hasProperty($elName, $propName, DOMElement $node)
	{
		if (!empty(self::$htmlElements[$elName][$propName]))
			if (!isset(self::$htmlElements[$elName][$propName . '0'])
			 || $this->evaluate(self::$htmlElements[$elName][$propName . '0'], $node))
				return \true;
		return \false;
	}
	protected static function match($bitfield1, $bitfield2)
	{
		return (\trim($bitfield1 & $bitfield2, "\0") !== '');
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Helpers;
use DOMAttr;
use DOMCharacterData;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMProcessingInstruction;
use DOMXPath;
use RuntimeException;
use s9e\TextFormatter\Configurator\Exceptions\InvalidXslException;
use s9e\TextFormatter\Configurator\Helpers\RegexpBuilder;
abstract class TemplateHelper
{
	const XMLNS_XSL = 'http://www.w3.org/1999/XSL/Transform';
	public static function getAttributesByRegexp(DOMDocument $dom, $regexp)
	{
		$xpath = new DOMXPath($dom);
		$nodes = array();
		foreach ($xpath->query('//@*') as $attribute)
			if (\preg_match($regexp, $attribute->name))
				$nodes[] = $attribute;
		foreach ($xpath->query('//xsl:attribute') as $attribute)
			if (\preg_match($regexp, $attribute->getAttribute('name')))
				$nodes[] = $attribute;
		foreach ($xpath->query('//xsl:copy-of') as $node)
		{
			$expr = $node->getAttribute('select');
			if (\preg_match('/^@(\\w+)$/', $expr, $m)
			 && \preg_match($regexp, $m[1]))
				$nodes[] = $node;
		}
		return $nodes;
	}
	public static function getCSSNodes(DOMDocument $dom)
	{
		$regexp = '/^style$/i';
		$nodes  = \array_merge(
			self::getAttributesByRegexp($dom, $regexp),
			self::getElementsByRegexp($dom, '/^style$/i')
		);
		return $nodes;
	}
	public static function getElementsByRegexp(DOMDocument $dom, $regexp)
	{
		$xpath = new DOMXPath($dom);
		$nodes = array();
		foreach ($xpath->query('//*') as $element)
			if (\preg_match($regexp, $element->localName))
				$nodes[] = $element;
		foreach ($xpath->query('//xsl:element') as $element)
			if (\preg_match($regexp, $element->getAttribute('name')))
				$nodes[] = $element;
		foreach ($xpath->query('//xsl:copy-of') as $node)
		{
			$expr = $node->getAttribute('select');
			if (\preg_match('/^\\w+$/', $expr)
			 && \preg_match($regexp, $expr))
				$nodes[] = $node;
		}
		return $nodes;
	}
	public static function getJSNodes(DOMDocument $dom)
	{
		$regexp = '/^(?>data-s9e-livepreview-postprocess$|on)/i';
		$nodes  = \array_merge(
			self::getAttributesByRegexp($dom, $regexp),
			self::getElementsByRegexp($dom, '/^script$/i')
		);
		return $nodes;
	}
	public static function getMetaElementsRegexp(array $templates)
	{
		$exprs = array();
		$xsl = '<xsl:template xmlns:xsl="http://www.w3.org/1999/XSL/Transform">' . \implode('', $templates) . '</xsl:template>';
		$dom = new DOMDocument;
		$dom->loadXML($xsl);
		$xpath = new DOMXPath($dom);
		$query = '//xsl:*/@*[contains("matchselectest", name())]';
		foreach ($xpath->query($query) as $attribute)
			$exprs[] = $attribute->value;
		$query = '//*[namespace-uri() != "' . self::XMLNS_XSL . '"]/@*';
		foreach ($xpath->query($query) as $attribute)
			foreach (AVTHelper::parse($attribute->value) as $token)
				if ($token[0] === 'expression')
					$exprs[] = $token[1];
		$tagNames = array(
			'e' => \true,
			'i' => \true,
			's' => \true
		);
		foreach (\array_keys($tagNames) as $tagName)
			if (isset($templates[$tagName]) && $templates[$tagName] !== '')
				unset($tagNames[$tagName]);
		$regexp = '(\\b(?<![$@])(' . \implode('|', \array_keys($tagNames)) . ')(?!-)\\b)';
		\preg_match_all($regexp, \implode("\n", $exprs), $m);
		foreach ($m[0] as $tagName)
			unset($tagNames[$tagName]);
		if (empty($tagNames))
			return '((?!))';
		return '(<' . RegexpBuilder::fromList(\array_keys($tagNames)) . '>[^<]*</[^>]+>)';
	}
	public static function getObjectParamsByRegexp(DOMDocument $dom, $regexp)
	{
		$xpath = new DOMXPath($dom);
		$nodes = array();
		foreach (self::getAttributesByRegexp($dom, $regexp) as $attribute)
			if ($attribute->nodeType === \XML_ATTRIBUTE_NODE)
			{
				if (\strtolower($attribute->parentNode->localName) === 'embed')
					$nodes[] = $attribute;
			}
			elseif ($xpath->evaluate('ancestor::embed', $attribute))
				$nodes[] = $attribute;
		foreach ($dom->getElementsByTagName('object') as $object)
			foreach ($object->getElementsByTagName('param') as $param)
				if (\preg_match($regexp, $param->getAttribute('name')))
					$nodes[] = $param;
		return $nodes;
	}
	public static function getParametersFromXSL($xsl)
	{
		$paramNames = array();
		$xsl = '<xsl:stylesheet xmlns:xsl="' . self::XMLNS_XSL . '"><xsl:template>'
		     . $xsl
		     . '</xsl:template></xsl:stylesheet>';
		$dom = new DOMDocument;
		$dom->loadXML($xsl);
		$xpath = new DOMXPath($dom);
		$query = '//xsl:*/@match | //xsl:*/@select | //xsl:*/@test';
		foreach ($xpath->query($query) as $attribute)
			foreach (XPathHelper::getVariables($attribute->value) as $varName)
			{
				$varQuery = 'ancestor-or-self::*/preceding-sibling::xsl:variable[@name="' . $varName . '"]';
				if (!$xpath->query($varQuery, $attribute)->length)
					$paramNames[] = $varName;
			}
		$query = '//*[namespace-uri() != "' . self::XMLNS_XSL . '"]/@*[contains(., "{")]';
		foreach ($xpath->query($query) as $attribute)
		{
			$tokens = AVTHelper::parse($attribute->value);
			foreach ($tokens as $token)
			{
				if ($token[0] !== 'expression')
					continue;
				foreach (XPathHelper::getVariables($token[1]) as $varName)
				{
					$varQuery = 'ancestor-or-self::*/preceding-sibling::xsl:variable[@name="' . $varName . '"]';
					if (!$xpath->query($varQuery, $attribute)->length)
						$paramNames[] = $varName;
				}
			}
		}
		$paramNames = \array_unique($paramNames);
		\sort($paramNames);
		return $paramNames;
	}
	public static function getURLNodes(DOMDocument $dom)
	{
		$regexp = '/(?>^(?>action|background|c(?>ite|lassid|odebase)|data|formaction|href|icon|longdesc|manifest|p(?>luginspage|oster|rofile)|usemap)|src)$/i';
		$nodes  = self::getAttributesByRegexp($dom, $regexp);
		foreach (self::getObjectParamsByRegexp($dom, '/^(?:dataurl|movie)$/i') as $param)
		{
			$node = $param->getAttributeNode('value');
			if ($node)
				$nodes[] = $node;
		}
		return $nodes;
	}
	public static function highlightNode(DOMNode $node, $prepend, $append)
	{
		$uniqid = \uniqid('_');
		if ($node instanceof DOMAttr)
			$node->value .= $uniqid;
		elseif ($node instanceof DOMElement)
			$node->setAttribute($uniqid, '');
		elseif ($node instanceof DOMCharacterData
		     || $node instanceof DOMProcessingInstruction)
			$node->data .= $uniqid;
		$dom = $node->ownerDocument;
		$dom->formatOutput = \true;
		$docXml = self::innerXML($dom->documentElement);
		$docXml = \trim(\str_replace("\n  ", "\n", $docXml));
		$nodeHtml = \htmlspecialchars(\trim($dom->saveXML($node)));
		$docHtml  = \htmlspecialchars($docXml);
		$html = \str_replace($nodeHtml, $prepend . $nodeHtml . $append, $docHtml);
		if ($node instanceof DOMAttr)
		{
			$node->value = \substr($node->value, 0, -\strlen($uniqid));
			$html = \str_replace($uniqid, '', $html);
		}
		elseif ($node instanceof DOMElement)
		{
			$node->removeAttribute($uniqid);
			$html = \str_replace(' ' . $uniqid . '=&quot;&quot;', '', $html);
		}
		elseif ($node instanceof DOMCharacterData
		     || $node instanceof DOMProcessingInstruction)
		{
			$node->data .= $uniqid;
			$html = \str_replace($uniqid, '', $html);
		}
		return $html;
	}
	public static function loadTemplate($template)
	{
		$dom = self::loadTemplateAsXML($template);
		if ($dom)
			return $dom;
		$dom = self::loadTemplateAsXML(self::fixEntities($template));
		if ($dom)
			return $dom;
		if (\strpos($template, '<xsl:') !== \false)
		{
			$error = \libxml_get_last_error();
			throw new InvalidXslException($error->message);
		}
		return self::loadTemplateAsHTML($template);
	}
	public static function replaceHomogeneousTemplates(array &$templates, $minCount = 3)
	{
		$tagNames = array();
		$expr = 'name()';
		foreach ($templates as $tagName => $template)
		{
			$elName = \strtolower(\preg_replace('/^[^:]+:/', '', $tagName));
			if ($template === '<' . $elName . '><xsl:apply-templates/></' . $elName . '>')
			{
				$tagNames[] = $tagName;
				if (\strpos($tagName, ':') !== \false)
					$expr = 'local-name()';
			}
		}
		if (\count($tagNames) < $minCount)
			return;
		$chars = \preg_replace('/[^A-Z]+/', '', \count_chars(\implode('', $tagNames), 3));
		if (\is_string($chars) && $chars !== '')
			$expr = 'translate(' . $expr . ",'" . $chars . "','" . \strtolower($chars) . "')";
		$template = '<xsl:element name="{' . $expr . '}"><xsl:apply-templates/></xsl:element>';
		foreach ($tagNames as $tagName)
			$templates[$tagName] = $template;
	}
	public static function replaceTokens($template, $regexp, $fn)
	{
		if ($template === '')
			return $template;
		$dom   = self::loadTemplate($template);
		$xpath = new DOMXPath($dom);
		foreach ($xpath->query('//@*') as $attribute)
		{
			$attrValue = \preg_replace_callback(
				$regexp,
				function ($m) use ($fn, $attribute)
				{
					$replacement = $fn($m, $attribute);
					if ($replacement[0] === 'expression')
						return '{' . $replacement[1] . '}';
					elseif ($replacement[0] === 'passthrough')
						return '{.}';
					else
						return $replacement[1];
				},
				$attribute->value
			);
			$attribute->value = \htmlspecialchars($attrValue, \ENT_COMPAT, 'UTF-8');
		}
		foreach ($xpath->query('//text()') as $node)
		{
			\preg_match_all(
				$regexp,
				$node->textContent,
				$matches,
				\PREG_SET_ORDER | \PREG_OFFSET_CAPTURE
			);
			if (empty($matches))
				continue;
			$parentNode = $node->parentNode;
			$lastPos = 0;
			foreach ($matches as $m)
			{
				$pos = $m[0][1];
				if ($pos > $lastPos)
					$parentNode->insertBefore(
						$dom->createTextNode(
							\substr($node->textContent, $lastPos, $pos - $lastPos)
						),
						$node
					);
				$lastPos = $pos + \strlen($m[0][0]);
				$_m = array();
				foreach ($m as $capture)
					$_m[] = $capture[0];
				$replacement = $fn($_m, $node);
				if ($replacement[0] === 'expression')
					$parentNode
						->insertBefore(
							$dom->createElementNS(self::XMLNS_XSL, 'xsl:value-of'),
							$node
						)
						->setAttribute('select', $replacement[1]);
				elseif ($replacement[0] === 'passthrough')
					$parentNode->insertBefore(
						$dom->createElementNS(self::XMLNS_XSL, 'xsl:apply-templates'),
						$node
					);
				else
					$parentNode->insertBefore($dom->createTextNode($replacement[1]), $node);
			}
			$text = \substr($node->textContent, $lastPos);
			if ($text > '')
				$parentNode->insertBefore($dom->createTextNode($text), $node);
			$parentNode->removeChild($node);
		}
		return self::saveTemplate($dom);
	}
	public static function saveTemplate(DOMDocument $dom)
	{
		return self::innerXML($dom->documentElement);
	}
	protected static function fixEntities($template)
	{
		return \preg_replace_callback(
			'(&(?!quot;|amp;|apos;|lt;|gt;)\\w+;)',
			function ($m)
			{
				return \html_entity_decode($m[0], \ENT_NOQUOTES, 'UTF-8');
			},
			\preg_replace('(&(?![A-Za-z0-9]+;|#\\d+;|#x[A-Fa-f0-9]+;))', '&amp;', $template)
		);
	}
	protected static function innerXML(DOMElement $element)
	{
		$xml = $element->ownerDocument->saveXML($element);
		$pos = 1 + \strpos($xml, '>');
		$len = \strrpos($xml, '<') - $pos;
		if ($len < 1)
			return '';
		$xml = \substr($xml, $pos, $len);
		return $xml;
	}
	protected static function loadTemplateAsHTML($template)
	{
		$dom  = new DOMDocument;
		$html = '<?xml version="1.0" encoding="utf-8" ?><html><body><div>' . $template . '</div></body></html>';
		$useErrors = \libxml_use_internal_errors(\true);
		$dom->loadHTML($html);
		self::removeInvalidAttributes($dom);
		\libxml_use_internal_errors($useErrors);
		$xml = '<?xml version="1.0" encoding="utf-8" ?><xsl:template xmlns:xsl="' . self::XMLNS_XSL . '">' . self::innerXML($dom->documentElement->firstChild->firstChild) . '</xsl:template>';
		$useErrors = \libxml_use_internal_errors(\true);
		$dom->loadXML($xml);
		\libxml_use_internal_errors($useErrors);
		return $dom;
	}
	protected static function loadTemplateAsXML($template)
	{
		$xml = '<?xml version="1.0" encoding="utf-8" ?><xsl:template xmlns:xsl="' . self::XMLNS_XSL . '">' . $template . '</xsl:template>';
		$useErrors = \libxml_use_internal_errors(\true);
		$dom       = new DOMDocument;
		$success   = $dom->loadXML($xml);
		self::removeInvalidAttributes($dom);
		\libxml_use_internal_errors($useErrors);
		return ($success) ? $dom : \false;
	}
	protected static function removeInvalidAttributes(DOMDocument $dom)
	{
		$xpath = new DOMXPath($dom);
		foreach ($xpath->query('//@*') as $attribute)
			if (!\preg_match('(^(?:[-\\w]+:)?(?!\\d)[-\\w]+$)D', $attribute->nodeName))
				$attribute->parentNode->removeAttributeNode($attribute);
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Helpers;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use RuntimeException;
class TemplateParser
{
	const XMLNS_XSL = 'http://www.w3.org/1999/XSL/Transform';
	public static $voidRegexp = '/^(?:area|base|br|col|command|embed|hr|img|input|keygen|link|meta|param|source|track|wbr)$/Di';
	public static function parse($template)
	{
		$xsl = '<xsl:template xmlns:xsl="' . self::XMLNS_XSL . '">' . $template . '</xsl:template>';
		$dom = new DOMDocument;
		$dom->loadXML($xsl);
		$ir = new DOMDocument;
		$ir->loadXML('<template/>');
		self::parseChildren($ir->documentElement, $dom->documentElement);
		self::normalize($ir);
		return $ir;
	}
	public static function parseEqualityExpr($expr)
	{
		$eq = '(?<equality>(?<key>@[-\\w]+|\\$\\w+|\\.)(?<operator>\\s*=\\s*)(?:(?<literal>(?<string>"[^"]*"|\'[^\']*\')|0|[1-9][0-9]*)|(?<concat>concat\\(\\s*(?&string)\\s*(?:,\\s*(?&string)\\s*)+\\)))|(?:(?<literal>(?&literal))|(?<concat>(?&concat)))(?&operator)(?<key>(?&key)))';
		$regexp = '(^(?J)\\s*' . $eq . '\\s*(?:or\\s*(?&equality)\\s*)*$)';
		if (!\preg_match($regexp, $expr))
			return \false;
		\preg_match_all("((?J)$eq)", $expr, $matches, \PREG_SET_ORDER);
		$map = array();
		foreach ($matches as $m)
		{
			$key = $m['key'];
			if (!empty($m['concat']))
			{
				\preg_match_all('(\'[^\']*\'|"[^"]*")', $m['concat'], $strings);
				$value = '';
				foreach ($strings[0] as $string)
					$value .= \substr($string, 1, -1);
			}
			else
			{
				$value = $m['literal'];
				if ($value[0] === "'" || $value[0] === '"')
					$value = \substr($value, 1, -1);
			}
			$map[$key][] = $value;
		}
		return $map;
	}
	protected static function parseChildren(DOMElement $ir, DOMElement $parent)
	{
		foreach ($parent->childNodes as $child)
		{
			switch ($child->nodeType)
			{
				case \XML_COMMENT_NODE:
					break;
				case \XML_TEXT_NODE:
					if (\trim($child->textContent) !== '')
						self::appendOutput($ir, 'literal', $child->textContent);
					break;
				case \XML_ELEMENT_NODE:
					self::parseNode($ir, $child);
					break;
				default:
					throw new RuntimeException("Cannot parse node '" . $child->nodeName . "''");
			}
		}
	}
	protected static function parseNode(DOMElement $ir, DOMElement $node)
	{
		if ($node->namespaceURI === self::XMLNS_XSL)
		{
			$methodName = 'parseXsl' . \str_replace(' ', '', \ucwords(\str_replace('-', ' ', $node->localName)));
			if (!\method_exists(__CLASS__, $methodName))
				throw new RuntimeException("Element '" . $node->nodeName . "' is not supported");
			return self::$methodName($ir, $node);
		}
		if (!\is_null($node->namespaceURI))
			throw new RuntimeException("Namespaced element '" . $node->nodeName . "' is not supported");
		$element = self::appendElement($ir, 'element');
		$element->setAttribute('name', $node->localName);
		foreach ($node->attributes as $attribute)
		{
			$irAttribute = self::appendElement($element, 'attribute');
			$irAttribute->setAttribute('name', $attribute->name);
			self::appendOutput($irAttribute, 'avt', $attribute->value);
		}
		self::parseChildren($element, $node);
	}
	protected static function parseXslApplyTemplates(DOMElement $ir, DOMElement $node)
	{
		$applyTemplates = self::appendElement($ir, 'applyTemplates');
		if ($node->hasAttribute('select'))
			$applyTemplates->setAttribute(
				'select',
				$node->getAttribute('select')
			);
	}
	protected static function parseXslAttribute(DOMElement $ir, DOMElement $node)
	{
		$attrName = $node->getAttribute('name');
		if ($attrName !== '')
		{
			$attribute = self::appendElement($ir, 'attribute');
			$attribute->setAttribute('name', $attrName);
			self::parseChildren($attribute, $node);
		}
	}
	protected static function parseXslChoose(DOMElement $ir, DOMElement $node)
	{
		$switch = self::appendElement($ir, 'switch');
		foreach ($node->getElementsByTagNameNS(self::XMLNS_XSL, 'when') as $when)
		{
			if ($when->parentNode !== $node)
				continue;
			$case = self::appendElement($switch, 'case');
			$case->setAttribute('test', $when->getAttribute('test'));
			self::parseChildren($case, $when);
		}
		foreach ($node->getElementsByTagNameNS(self::XMLNS_XSL, 'otherwise') as $otherwise)
		{
			if ($otherwise->parentNode !== $node)
				continue;
			$case = self::appendElement($switch, 'case');
			self::parseChildren($case, $otherwise);
			break;
		}
	}
	protected static function parseXslComment(DOMElement $ir, DOMElement $node)
	{
		$comment = self::appendElement($ir, 'comment');
		self::parseChildren($comment, $node);
	}
	protected static function parseXslCopyOf(DOMElement $ir, DOMElement $node)
	{
		$expr = $node->getAttribute('select');
		if (\preg_match('#^@([-\\w]+)$#', $expr, $m))
		{
			$switch = self::appendElement($ir, 'switch');
			$case   = self::appendElement($switch, 'case');
			$case->setAttribute('test', $expr);
			$attribute = self::appendElement($case, 'attribute');
			$attribute->setAttribute('name', $m[1]);
			self::appendOutput($attribute, 'xpath', $expr);
			return;
		}
		if ($expr === '@*')
		{
			self::appendElement($ir, 'copyOfAttributes');
			return;
		}
		throw new RuntimeException("Unsupported <xsl:copy-of/> expression '" . $expr . "'");
	}
	protected static function parseXslElement(DOMElement $ir, DOMElement $node)
	{
		$elName = $node->getAttribute('name');
		if ($elName !== '')
		{
			$element = self::appendElement($ir, 'element');
			$element->setAttribute('name', $elName);
			self::parseChildren($element, $node);
		}
	}
	protected static function parseXslIf(DOMElement $ir, DOMElement $node)
	{
		$switch = self::appendElement($ir, 'switch');
		$case   = self::appendElement($switch, 'case');
		$case->setAttribute('test', $node->getAttribute('test'));
		self::parseChildren($case, $node);
	}
	protected static function parseXslText(DOMElement $ir, DOMElement $node)
	{
		self::appendOutput($ir, 'literal', $node->textContent);
	}
	protected static function parseXslValueOf(DOMElement $ir, DOMElement $node)
	{
		self::appendOutput($ir, 'xpath', $node->getAttribute('select'));
	}
	protected static function normalize(DOMDocument $ir)
	{
		self::addDefaultCase($ir);
		self::addElementIds($ir);
		self::addCloseTagElements($ir);
		self::markEmptyElements($ir);
		self::optimize($ir);
		self::markConditionalCloseTagElements($ir);
		self::setOutputContext($ir);
		self::markBranchTables($ir);
	}
	protected static function addDefaultCase(DOMDocument $ir)
	{
		$xpath = new DOMXPath($ir);
		foreach ($xpath->query('//switch[not(case[not(@test)])]') as $switch)
			self::appendElement($switch, 'case');
	}
	protected static function addElementIds(DOMDocument $ir)
	{
		$id = 0;
		foreach ($ir->getElementsByTagName('element') as $element)
			$element->setAttribute('id', ++$id);
	}
	protected static function addCloseTagElements(DOMDocument $ir)
	{
		$xpath = new DOMXPath($ir);
		$exprs = array(
			'//applyTemplates[not(ancestor::attribute)]',
			'//comment',
			'//element',
			'//output[not(ancestor::attribute)]'
		);
		foreach ($xpath->query(\implode('|', $exprs)) as $node)
		{
			$parentElementId = self::getParentElementId($node);
			if (isset($parentElementId))
				$node->parentNode
				     ->insertBefore($ir->createElement('closeTag'), $node)
				     ->setAttribute('id', $parentElementId);
			if ($node->nodeName === 'element')
			{
				$id = $node->getAttribute('id');
				self::appendElement($node, 'closeTag')->setAttribute('id', $id);
			}
		}
	}
	protected static function markConditionalCloseTagElements(DOMDocument $ir)
	{
		$xpath = new DOMXPath($ir);
		foreach ($ir->getElementsByTagName('closeTag') as $closeTag)
		{
			$id = $closeTag->getAttribute('id');
			$query = 'ancestor::switch/following-sibling::*/descendant-or-self::closeTag[@id = "' . $id . '"]';
			foreach ($xpath->query($query, $closeTag) as $following)
			{
				$following->setAttribute('check', '');
				$closeTag->setAttribute('set', '');
			}
		}
	}
	protected static function markEmptyElements(DOMDocument $ir)
	{
		foreach ($ir->getElementsByTagName('element') as $element)
		{
			$elName = $element->getAttribute('name');
			if (\strpos($elName, '{') !== \false)
				$element->setAttribute('void', 'maybe');
			elseif (\preg_match(self::$voidRegexp, $elName))
				$element->setAttribute('void', 'yes');
			$isEmpty = self::isEmpty($element);
			if ($isEmpty === 'yes' || $isEmpty === 'maybe')
				$element->setAttribute('empty', $isEmpty);
		}
	}
	protected static function getOutputContext(DOMNode $output)
	{
		$xpath = new DOMXPath($output->ownerDocument);
		if ($xpath->evaluate('boolean(ancestor::attribute)', $output))
			return 'attribute';
		if ($xpath->evaluate('boolean(ancestor::element[@name="script"])', $output))
			return 'raw';
		return 'text';
	}
	protected static function getParentElementId(DOMNode $node)
	{
		$parentNode = $node->parentNode;
		while (isset($parentNode))
		{
			if ($parentNode->nodeName === 'element')
				return $parentNode->getAttribute('id');
			$parentNode = $parentNode->parentNode;
		}
	}
	protected static function setOutputContext(DOMDocument $ir)
	{
		foreach ($ir->getElementsByTagName('output') as $output)
			$output->setAttribute('escape', self::getOutputContext($output));
	}
	protected static function optimize(DOMDocument $ir)
	{
		$xml = $ir->saveXML();
		$remainingLoops = 10;
		do
		{
			$old = $xml;
			self::optimizeCloseTagElements($ir);
			$xml = $ir->saveXML();
		}
		while (--$remainingLoops > 0 && $xml !== $old);
		self::removeCloseTagSiblings($ir);
		self::removeContentFromVoidElements($ir);
		self::mergeConsecutiveLiteralOutputElements($ir);
		self::removeEmptyDefaultCases($ir);
	}
	protected static function removeCloseTagSiblings(DOMDocument $ir)
	{
		$xpath = new DOMXPath($ir);
		$query = '//switch[not(case[not(closeTag)])]/following-sibling::closeTag';
		foreach ($xpath->query($query) as $closeTag)
			$closeTag->parentNode->removeChild($closeTag);
	}
	protected static function removeEmptyDefaultCases(DOMDocument $ir)
	{
		$xpath = new DOMXPath($ir);
		foreach ($xpath->query('//case[not(@test | node())]') as $case)
			$case->parentNode->removeChild($case);
	}
	protected static function mergeConsecutiveLiteralOutputElements(DOMDocument $ir)
	{
		$xpath = new DOMXPath($ir);
		foreach ($xpath->query('//output[@type="literal"]') as $output)
			while ($output->nextSibling
				&& $output->nextSibling->nodeName === 'output'
				&& $output->nextSibling->getAttribute('type') === 'literal')
			{
				$output->nodeValue
					= \htmlspecialchars($output->nodeValue . $output->nextSibling->nodeValue);
				$output->parentNode->removeChild($output->nextSibling);
			}
	}
	protected static function optimizeCloseTagElements(DOMDocument $ir)
	{
		self::cloneCloseTagElementsIntoSwitch($ir);
		self::cloneCloseTagElementsOutOfSwitch($ir);
		self::removeRedundantCloseTagElementsInSwitch($ir);
		self::removeRedundantCloseTagElements($ir);
	}
	protected static function cloneCloseTagElementsIntoSwitch(DOMDocument $ir)
	{
		$xpath = new DOMXPath($ir);
		$query = '//switch[name(following-sibling::*) = "closeTag"]';
		foreach ($xpath->query($query) as $switch)
		{
			$closeTag = $switch->nextSibling;
			foreach ($switch->childNodes as $case)
				if (!$case->lastChild || $case->lastChild->nodeName !== 'closeTag')
					$case->appendChild($closeTag->cloneNode());
		}
	}
	protected static function cloneCloseTagElementsOutOfSwitch(DOMDocument $ir)
	{
		$xpath = new DOMXPath($ir);
		$query = '//switch[not(preceding-sibling::closeTag)]';
		foreach ($xpath->query($query) as $switch)
		{
			foreach ($switch->childNodes as $case)
				if (!$case->firstChild || $case->firstChild->nodeName !== 'closeTag')
					continue 2;
			$switch->parentNode->insertBefore($switch->lastChild->firstChild->cloneNode(), $switch);
		}
	}
	protected static function removeRedundantCloseTagElementsInSwitch(DOMDocument $ir)
	{
		$xpath = new DOMXPath($ir);
		$query = '//switch[name(following-sibling::*) = "closeTag"]';
		foreach ($xpath->query($query) as $switch)
			foreach ($switch->childNodes as $case)
				while ($case->lastChild && $case->lastChild->nodeName === 'closeTag')
					$case->removeChild($case->lastChild);
	}
	protected static function removeRedundantCloseTagElements(DOMDocument $ir)
	{
		$xpath = new DOMXPath($ir);
		foreach ($xpath->query('//closeTag') as $closeTag)
		{
			$id    = $closeTag->getAttribute('id');
			$query = 'following-sibling::*/descendant-or-self::closeTag[@id="' . $id . '"]';
			foreach ($xpath->query($query, $closeTag) as $dupe)
				$dupe->parentNode->removeChild($dupe);
		}
	}
	protected static function removeContentFromVoidElements(DOMDocument $ir)
	{
		$xpath = new DOMXPath($ir);
		foreach ($xpath->query('//element[@void="yes"]') as $element)
		{
			$id    = $element->getAttribute('id');
			$query = './/closeTag[@id="' . $id . '"]/following-sibling::*';
			foreach ($xpath->query($query, $element) as $node)
				$node->parentNode->removeChild($node);
		}
	}
	protected static function markBranchTables(DOMDocument $ir)
	{
		$xpath = new DOMXPath($ir);
		foreach ($xpath->query('//switch[case[2][@test]]') as $switch)
		{
			$key = \null;
			$branchValues = array();
			foreach ($switch->childNodes as $i => $case)
			{
				if (!$case->hasAttribute('test'))
					continue;
				$map = self::parseEqualityExpr($case->getAttribute('test'));
				if ($map === \false)
					continue 2;
				if (\count($map) !== 1)
					continue 2;
				if (isset($key) && $key !== \key($map))
					continue 2;
				$key = \key($map);
				$branchValues[$i] = \end($map);
			}
			$switch->setAttribute('branch-key', $key);
			foreach ($branchValues as $i => $values)
			{
				\sort($values);
				$switch->childNodes->item($i)->setAttribute('branch-values', \serialize($values));
			}
		}
	}
	protected static function appendElement(DOMElement $parentNode, $name, $value = '')
	{
		if ($value === '')
			$element = $parentNode->ownerDocument->createElement($name);
		else
			$element = $parentNode->ownerDocument->createElement($name, $value);
		$parentNode->appendChild($element);
		return $element;
	}
	protected static function appendOutput(DOMElement $ir, $type, $content)
	{
		if ($type === 'avt')
		{
			foreach (AVTHelper::parse($content) as $token)
			{
				$type = ($token[0] === 'expression') ? 'xpath' : 'literal';
				self::appendOutput($ir, $type, $token[1]);
			}
			return;
		}
		if ($type === 'xpath')
			$content = \trim($content);
		if ($type === 'literal' && $content === '')
			return;
		self::appendElement($ir, 'output', \htmlspecialchars($content))
			->setAttribute('type', $type);
	}
	protected static function isEmpty(DOMElement $ir)
	{
		$xpath = new DOMXPath($ir->ownerDocument);
		if ($xpath->evaluate('count(comment | element | output[@type="literal"])', $ir))
			return 'no';
		$cases = array();
		foreach ($xpath->query('switch/case', $ir) as $case)
			$cases[self::isEmpty($case)] = 1;
		if (isset($cases['maybe']))
			return 'maybe';
		if (isset($cases['no']))
		{
			if (!isset($cases['yes']))
				return 'no';
			return 'maybe';
		}
		if ($xpath->evaluate('count(applyTemplates | output[@type="xpath"])', $ir))
			return 'maybe';
		return 'yes';
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Helpers;
use RuntimeException;
abstract class XPathHelper
{
	public static function export($str)
	{
		if (\strpos($str, "'") === \false)
			return "'" . $str . "'";
		if (\strpos($str, '"') === \false)
			return '"' . $str . '"';
		$toks = array();
		$c = '"';
		$pos = 0;
		while ($pos < \strlen($str))
		{
			$spn = \strcspn($str, $c, $pos);
			if ($spn)
			{
				$toks[] = $c . \substr($str, $pos, $spn) . $c;
				$pos += $spn;
			}
			$c = ($c === '"') ? "'" : '"';
		}
		return 'concat(' . \implode(',', $toks) . ')';
	}
	public static function getVariables($expr)
	{
		$expr = \preg_replace('/(["\']).*?\\1/s', '$1$1', $expr);
		\preg_match_all('/\\$(\\w+)/', $expr, $matches);
		$varNames = \array_unique($matches[1]);
		\sort($varNames);
		return $varNames;
	}
	public static function isExpressionNumeric($expr)
	{
		$expr = \strrev(\preg_replace('(\\((?!\\s*(?!vid(?!\\w))\\w))', ' ', \strrev($expr)));
		$expr = \str_replace(')', ' ', $expr);
		if (\preg_match('(^\\s*([$@][-\\w]++|-?\\d++)(?>\\s*(?>[-+*]|div)\\s*(?1))++\\s*$)', $expr))
			return \true;
		return \false;
	}
	public static function minify($expr)
	{
		$old     = $expr;
		$strings = array();
		$expr = \preg_replace_callback(
			'/"[^"]*"|\'[^\']*\'/',
			function ($m) use (&$strings)
			{
				$uniqid = '(' . \sha1(\uniqid()) . ')';
				$strings[$uniqid] = $m[0];
				return $uniqid;
			},
			\trim($expr)
		);
		if (\preg_match('/[\'"]/', $expr))
			throw new RuntimeException("Cannot parse XPath expression '" . $old . "'");
		$expr = \preg_replace('/\\s+/', ' ', $expr);
		$expr = \preg_replace('/([-a-z_0-9]) ([^-a-z_0-9])/i', '$1$2', $expr);
		$expr = \preg_replace('/([^-a-z_0-9]) ([-a-z_0-9])/i', '$1$2', $expr);
		$expr = \preg_replace('/(?!- -)([^-a-z_0-9]) ([^-a-z_0-9])/i', '$1$2', $expr);
		$expr = \preg_replace('/ - ([a-z_0-9])/i', ' -$1', $expr);
		$expr = \preg_replace('/((?:^|[ \\(])\\d+) div ?/', '$1div', $expr);
		$expr = \preg_replace('/([^-a-z_0-9]div) (?=[$0-9@])/', '$1', $expr);
		$expr = \strtr($expr, $strings);
		return $expr;
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Items;
use DOMDocument;
use s9e\TextFormatter\Configurator\Helpers\TemplateForensics;
use s9e\TextFormatter\Configurator\Helpers\TemplateHelper;
use s9e\TextFormatter\Configurator\TemplateNormalizer;
class Template
{
	protected $forensics;
	protected $isNormalized = \false;
	protected $template;
	public function __construct($template)
	{
		$this->template = $template;
	}
	public function __call($methodName, $args)
	{
		return \call_user_func_array(array($this->getForensics(), $methodName), $args);
	}
	public function __toString()
	{
		return $this->template;
	}
	public function asDOM()
	{
		$xml = '<xsl:template xmlns:xsl="http://www.w3.org/1999/XSL/Transform">'
		     . $this->__toString()
		     . '</xsl:template>';
		$dom = new TemplateDocument($this);
		$dom->loadXML($xml);
		return $dom;
	}
	public function getCSSNodes()
	{
		return TemplateHelper::getCSSNodes($this->asDOM());
	}
	public function getForensics()
	{
		if (!isset($this->forensics))
			$this->forensics = new TemplateForensics($this->__toString());
		return $this->forensics;
	}
	public function getJSNodes()
	{
		return TemplateHelper::getJSNodes($this->asDOM());
	}
	public function getURLNodes()
	{
		return TemplateHelper::getURLNodes($this->asDOM());
	}
	public function getParameters()
	{
		return TemplateHelper::getParametersFromXSL($this->__toString());
	}
	public function isNormalized($bool = \null)
	{
		if (isset($bool))
			$this->isNormalized = $bool;
		return $this->isNormalized;
	}
	public function normalize(TemplateNormalizer $templateNormalizer)
	{
		$this->forensics    = \null;
		$this->template     = $templateNormalizer->normalizeTemplate($this->template);
		$this->isNormalized = \true;
	}
	public function replaceTokens($regexp, $fn)
	{
		$this->forensics    = \null;
		$this->template     = TemplateHelper::replaceTokens($this->template, $regexp, $fn);
		$this->isNormalized = \false;
	}
	public function setContent($template)
	{
		$this->forensics    = \null;
		$this->template     = (string) $template;
		$this->isNormalized = \false;
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\JavaScript;
use InvalidArgumentException;
class FunctionProvider
{
	public static $cache = array(
		'addslashes'=>'function(str)
{
	return str.replace(/["\'\\\\]/g, \'\\\\$&\').replace(/\\u0000/g, \'\\\\0\');
}',
		'dechex'=>'function(str)
{
	return parseInt(str).toString(16);
}',
		'intval'=>'function(str)
{
	return parseInt(str) || 0;
}',
		'ltrim'=>'function(str)
{
	return str.replace(/^[ \\n\\r\\t\\0\\x0B]+/g, \'\');
}',
		'mb_strtolower'=>'function(str)
{
	return str.toLowerCase();
}',
		'mb_strtoupper'=>'function(str)
{
	return str.toUpperCase();
}',
		'mt_rand'=>'function(min, max)
{
	return (min + Math.floor(Math.random() * (max + 1 - min)));
}',
		'rawurlencode'=>'function(str)
{
	return encodeURIComponent(str).replace(
		/[!\'()*]/g,
		/**
		* @param {!string} c
		*/
		function(c)
		{
			return \'%\' + c.charCodeAt(0).toString(16).toUpperCase();
		}
	);
}',
		'rtrim'=>'function(str)
{
	return str.replace(/[ \\n\\r\\t\\0\\x0B]+$/g, \'\');
}',
		'str_rot13'=>'function(str)
{
	return str.replace(
		/[a-z]/gi,
		function(c)
		{
			return String.fromCharCode(c.charCodeAt(0) + ((c.toLowerCase() < \'n\') ? 13 : -13));
		}
	);
}',
		'stripslashes'=>'function(str)
{
	// NOTE: this will not correctly transform \\0 into a NULL byte. I consider this a feature
	//       rather than a bug. There\'s no reason to use NULL bytes in a text.
	return str.replace(/\\\\([\\s\\S]?)/g, \'\\\\1\');
}',
		'strrev'=>'function(str)
{
	return str.split(\'\').reverse().join(\'\');
}',
		'strtolower'=>'function(str)
{
	return str.toLowerCase();
}',
		'strtotime'=>'function(str)
{
	return Date.parse(str) / 1000;
}',
		'strtoupper'=>'function(str)
{
	return str.toUpperCase();
}',
		'trim'=>'function(str)
{
	return str.replace(/^[ \\n\\r\\t\\0\\x0B]+/g, \'\').replace(/[ \\n\\r\\t\\0\\x0B]+$/g, \'\');
}',
		'ucfirst'=>'function(str)
{
	return str.charAt(0).toUpperCase() + str.substr(1);
}',
		'ucwords'=>'function(str)
{
	return str.replace(
		/(?:^|\\s)[a-z]/g,
		function(m)
		{
			return m.toUpperCase()
		}
	);
}',
		'urldecode'=>'function(str)
{
	return decodeURIComponent(str);
}',
		'urlencode'=>'function(str)
{
	return encodeURIComponent(str);
}'
	);
	public static function get($funcName)
	{
		if (isset(self::$cache[$funcName]))
			return self::$cache[$funcName];
		if (\preg_match('(^[a-z_0-9]+$)D', $funcName))
		{
			$filepath = __DIR__ . '/Configurator/JavaScript/functions/' . $funcName . '.js';
			if (\file_exists($filepath))
				return \file_get_contents($filepath);
		}
		throw new InvalidArgumentException("Unknown function '" . $funcName . "'");
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator;
interface RendererGenerator
{
	public function getRenderer(Rendering $rendering);
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RendererGenerators\PHP;
abstract class AbstractOptimizer
{
	protected $cnt;
	protected $i;
	protected $changed;
	protected $tokens;
	public function optimize($php)
	{
		$this->reset($php);
		$this->optimizeTokens();
		if ($this->changed)
			$php = $this->serialize();
		unset($this->tokens);
		return $php;
	}
	abstract protected function optimizeTokens();
	protected function reset($php)
	{
		$this->tokens  = \token_get_all('<?php ' . $php);
		$this->i       = 0;
		$this->cnt     = \count($this->tokens);
		$this->changed = \false;
	}
	protected function serialize()
	{
		unset($this->tokens[0]);
		$php = '';
		foreach ($this->tokens as $token)
			$php .= (\is_string($token)) ? $token : $token[1];
		return $php;
	}
	protected function skipToString($str)
	{
		while (++$this->i < $this->cnt && $this->tokens[$this->i] !== $str);
	}
	protected function skipWhitespace()
	{
		while (++$this->i < $this->cnt && $this->tokens[$this->i][0] === \T_WHITESPACE);
	}
	protected function unindentBlock($start, $end)
	{
		$this->i = $start;
		do
		{
			if ($this->tokens[$this->i][0] === \T_WHITESPACE || $this->tokens[$this->i][0] === \T_DOC_COMMENT)
				$this->tokens[$this->i][1] = \preg_replace("/^\t/m", '', $this->tokens[$this->i][1]);
		}
		while (++$this->i <= $end);
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RendererGenerators\PHP;
class BranchOutputOptimizer
{
	protected $cnt;
	protected $i;
	protected $tokens;
	public function optimize(array $tokens)
	{
		$this->tokens = $tokens;
		$this->i      = 0;
		$this->cnt    = \count($this->tokens);
		$php = '';
		while (++$this->i < $this->cnt)
			if ($this->tokens[$this->i][0] === \T_IF)
				$php .= $this->serializeIfBlock($this->parseIfBlock());
			else
				$php .= $this->serializeToken($this->tokens[$this->i]);
		unset($this->tokens);
		return $php;
	}
	protected function captureOutput()
	{
		$expressions = array();
		while ($this->skipOutputAssignment())
		{
			do
			{
				$expressions[] = $this->captureOutputExpression();
			}
			while ($this->tokens[$this->i++] === '.');
		}
		return $expressions;
	}
	protected function captureOutputExpression()
	{
		$parens = 0;
		$php = '';
		do
		{
			if ($this->tokens[$this->i] === ';')
				break;
			elseif ($this->tokens[$this->i] === '.' && !$parens)
				break;
			elseif ($this->tokens[$this->i] === '(')
				++$parens;
			elseif ($this->tokens[$this->i] === ')')
				--$parens;
			$php .= $this->serializeToken($this->tokens[$this->i]);
		}
		while (++$this->i < $this->cnt);
		return $php;
	}
	protected function captureStructure()
	{
		$php = '';
		do
		{
			$php .= $this->serializeToken($this->tokens[$this->i]);
		}
		while ($this->tokens[++$this->i] !== '{');
		++$this->i;
		return $php;
	}
	protected function isBranchToken()
	{
		return \in_array($this->tokens[$this->i][0], array(\T_ELSE, \T_ELSEIF, \T_IF), \true);
	}
	protected function mergeIfBranches(array $branches)
	{
		$lastBranch = \end($branches);
		if ($lastBranch['structure'] === 'else')
		{
			$before = $this->optimizeBranchesHead($branches);
			$after  = $this->optimizeBranchesTail($branches);
		}
		else
			$before = $after = array();
		$source = '';
		foreach ($branches as $branch)
			$source .= $this->serializeBranch($branch);
		return array(
			'before' => $before,
			'source' => $source,
			'after'  => $after
		);
	}
	protected function mergeOutput(array $left, array $right)
	{
		if (empty($left))
			return $right;
		if (empty($right))
			return $left;
		$k = \count($left) - 1;
		if (\substr($left[$k], -1) === "'" && $right[0][0] === "'")
		{
			$right[0] = \substr($left[$k], 0, -1) . \substr($right[0], 1);
			unset($left[$k]);
		}
		return \array_merge($left, $right);
	}
	protected function optimizeBranchesHead(array &$branches)
	{
		$before = $this->optimizeBranchesOutput($branches, 'head');
		foreach ($branches as &$branch)
		{
			if ($branch['body'] !== '' || !empty($branch['tail']))
				continue;
			$branch['tail'] = \array_reverse($branch['head']);
			$branch['head'] = array();
		}
		unset($branch);
		return $before;
	}
	protected function optimizeBranchesOutput(array &$branches, $which)
	{
		$expressions = array();
		while (isset($branches[0][$which][0]))
		{
			$expr = $branches[0][$which][0];
			foreach ($branches as $branch)
				if (!isset($branch[$which][0]) || $branch[$which][0] !== $expr)
					break 2;
			$expressions[] = $expr;
			foreach ($branches as &$branch)
				\array_shift($branch[$which]);
			unset($branch);
		}
		return $expressions;
	}
	protected function optimizeBranchesTail(array &$branches)
	{
		return $this->optimizeBranchesOutput($branches, 'tail');
	}
	protected function parseBranch()
	{
		$structure = $this->captureStructure();
		$head = $this->captureOutput();
		$body = '';
		$tail = array();
		$braces = 0;
		do
		{
			$tail = $this->mergeOutput($tail, \array_reverse($this->captureOutput()));
			if ($this->tokens[$this->i] === '}' && !$braces)
				break;
			$body .= $this->serializeOutput(\array_reverse($tail));
			$tail  = array();
			if ($this->tokens[$this->i][0] === \T_IF)
			{
				$child = $this->parseIfBlock();
				if ($body === '')
					$head = $this->mergeOutput($head, $child['before']);
				else
					$body .= $this->serializeOutput($child['before']);
				$body .= $child['source'];
				$tail  = $child['after'];
			}
			else
			{
				$body .= $this->serializeToken($this->tokens[$this->i]);
				if ($this->tokens[$this->i] === '{')
					++$braces;
				elseif ($this->tokens[$this->i] === '}')
					--$braces;
			}
		}
		while (++$this->i < $this->cnt);
		return array(
			'structure' => $structure,
			'head'      => $head,
			'body'      => $body,
			'tail'      => $tail
		);
	}
	protected function parseIfBlock()
	{
		$branches = array();
		do
		{
			$branches[] = $this->parseBranch();
		}
		while (++$this->i < $this->cnt && $this->isBranchToken());
		--$this->i;
		return $this->mergeIfBranches($branches);
	}
	protected function serializeBranch(array $branch)
	{
		if ($branch['structure'] === 'else'
		 && $branch['body']      === ''
		 && empty($branch['head'])
		 && empty($branch['tail']))
			return '';
		return $branch['structure'] . '{' . $this->serializeOutput($branch['head']) . $branch['body'] . $this->serializeOutput(\array_reverse($branch['tail'])) . '}';
	}
	protected function serializeIfBlock(array $block)
	{
		return $this->serializeOutput($block['before']) . $block['source'] . $this->serializeOutput(\array_reverse($block['after']));
	}
	protected function serializeOutput(array $expressions)
	{
		if (empty($expressions))
			return '';
		return '$this->out.=' . \implode('.', $expressions) . ';';
	}
	protected function serializeToken($token)
	{
		return (\is_array($token)) ? $token[1] : $token;
	}
	protected function skipOutputAssignment()
	{
		if ($this->tokens[$this->i    ][0] !== \T_VARIABLE
		 || $this->tokens[$this->i    ][1] !== '$this'
		 || $this->tokens[$this->i + 1][0] !== \T_OBJECT_OPERATOR
		 || $this->tokens[$this->i + 2][0] !== \T_STRING
		 || $this->tokens[$this->i + 2][1] !== 'out'
		 || $this->tokens[$this->i + 3][0] !== \T_CONCAT_EQUAL)
			 return \false;
		$this->i += 4;
		return \true;
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RendererGenerators\PHP;
class Optimizer
{
	public $branchOutputOptimizer;
	protected $cnt;
	protected $i;
	public $maxLoops = 10;
	protected $tokens;
	public function __construct()
	{
		$this->branchOutputOptimizer = new BranchOutputOptimizer;
	}
	public function optimize($php)
	{
		$this->tokens = \token_get_all('<?php ' . $php);
		$this->cnt    = \count($this->tokens);
		$this->i      = 0;
		foreach ($this->tokens as &$token)
			if (\is_array($token))
				unset($token[2]);
		unset($token);
		$passes = array(
			'optimizeOutConcatEqual',
			'optimizeConcatenations',
			'optimizeHtmlspecialchars'
		);
		$remainingLoops = $this->maxLoops;
		do
		{
			$continue = \false;
			foreach ($passes as $pass)
			{
				$this->$pass();
				$cnt = \count($this->tokens);
				if ($this->cnt !== $cnt)
				{
					$this->tokens = \array_values($this->tokens);
					$this->cnt    = $cnt;
					$continue     = \true;
				}
			}
		}
		while ($continue && --$remainingLoops);
		$php = $this->branchOutputOptimizer->optimize($this->tokens);
		unset($this->tokens);
		return $php;
	}
	protected function isBetweenHtmlspecialcharCalls()
	{
		return ($this->tokens[$this->i + 1]    === array(\T_STRING, 'htmlspecialchars')
		     && $this->tokens[$this->i + 2]    === '('
		     && $this->tokens[$this->i - 1]    === ')'
		     && $this->tokens[$this->i - 2][0] === \T_LNUMBER
		     && $this->tokens[$this->i - 3]    === ',');
	}
	protected function isHtmlspecialcharSafeVar()
	{
		return ($this->tokens[$this->i    ]    === array(\T_VARIABLE,        '$node')
		     && $this->tokens[$this->i + 1]    === array(\T_OBJECT_OPERATOR, '->')
		     && ($this->tokens[$this->i + 2]   === array(\T_STRING,          'localName')
		      || $this->tokens[$this->i + 2]   === array(\T_STRING,          'nodeName'))
		     && $this->tokens[$this->i + 3]    === ','
		     && $this->tokens[$this->i + 4][0] === \T_LNUMBER
		     && $this->tokens[$this->i + 5]    === ')');
	}
	protected function isOutputAssignment()
	{
		return ($this->tokens[$this->i    ] === array(\T_VARIABLE,        '$this')
		     && $this->tokens[$this->i + 1] === array(\T_OBJECT_OPERATOR, '->')
		     && $this->tokens[$this->i + 2] === array(\T_STRING,          'out')
		     && $this->tokens[$this->i + 3] === array(\T_CONCAT_EQUAL,    '.='));
	}
	protected function isPrecededByOutputVar()
	{
		return ($this->tokens[$this->i - 1] === array(\T_STRING,          'out')
		     && $this->tokens[$this->i - 2] === array(\T_OBJECT_OPERATOR, '->')
		     && $this->tokens[$this->i - 3] === array(\T_VARIABLE,        '$this'));
	}
	protected function mergeConcatenatedHtmlSpecialChars()
	{
		if (!$this->isBetweenHtmlspecialcharCalls())
			 return \false;
		$escapeMode = $this->tokens[$this->i - 2][1];
		$startIndex = $this->i - 3;
		$endIndex = $this->i + 2;
		$this->i = $endIndex;
		$parens = 0;
		while (++$this->i < $this->cnt)
		{
			if ($this->tokens[$this->i] === ',' && !$parens)
				break;
			if ($this->tokens[$this->i] === '(')
				++$parens;
			elseif ($this->tokens[$this->i] === ')')
				--$parens;
		}
		if ($this->tokens[$this->i + 1] !== array(\T_LNUMBER, $escapeMode))
			return \false;
		$this->tokens[$startIndex] = '.';
		$this->i = $startIndex;
		while (++$this->i <= $endIndex)
			unset($this->tokens[$this->i]);
		return \true;
	}
	protected function mergeConcatenatedStrings()
	{
		if ($this->tokens[$this->i - 1][0]    !== \T_CONSTANT_ENCAPSED_STRING
		 || $this->tokens[$this->i + 1][0]    !== \T_CONSTANT_ENCAPSED_STRING
		 || $this->tokens[$this->i - 1][1][0] !== $this->tokens[$this->i + 1][1][0])
			return \false;
		$this->tokens[$this->i + 1][1] = \substr($this->tokens[$this->i - 1][1], 0, -1)
		                               . \substr($this->tokens[$this->i + 1][1], 1);
		unset($this->tokens[$this->i - 1]);
		unset($this->tokens[$this->i]);
		++$this->i;
		return \true;
	}
	protected function optimizeOutConcatEqual()
	{
		$this->i = 3;
		while ($this->skipTo(array(\T_CONCAT_EQUAL, '.=')))
		{
			if (!$this->isPrecededByOutputVar())
				 continue;
			while ($this->skipPast(';'))
			{
				if (!$this->isOutputAssignment())
					 break;
				$this->tokens[$this->i - 1] = '.';
				unset($this->tokens[$this->i++]);
				unset($this->tokens[$this->i++]);
				unset($this->tokens[$this->i++]);
				unset($this->tokens[$this->i++]);
			}
		}
	}
	protected function optimizeConcatenations()
	{
		$this->i = 1;
		while ($this->skipTo('.'))
			$this->mergeConcatenatedStrings() || $this->mergeConcatenatedHtmlSpecialChars();
	}
	protected function optimizeHtmlspecialchars()
	{
		$this->i = 0;
		while ($this->skipPast(array(\T_STRING, 'htmlspecialchars')))
			if ($this->tokens[$this->i] === '(')
			{
				++$this->i;
				$this->replaceHtmlspecialcharsLiteral() || $this->removeHtmlspecialcharsSafeVar();
			}
	}
	protected function removeHtmlspecialcharsSafeVar()
	{
		if (!$this->isHtmlspecialcharSafeVar())
			 return \false;
		unset($this->tokens[$this->i - 2]);
		unset($this->tokens[$this->i - 1]);
		unset($this->tokens[$this->i + 3]);
		unset($this->tokens[$this->i + 4]);
		unset($this->tokens[$this->i + 5]);
		$this->i += 6;
		return \true;
	}
	protected function replaceHtmlspecialcharsLiteral()
	{
		if ($this->tokens[$this->i    ][0] !== \T_CONSTANT_ENCAPSED_STRING
		 || $this->tokens[$this->i + 1]    !== ','
		 || $this->tokens[$this->i + 2][0] !== \T_LNUMBER
		 || $this->tokens[$this->i + 3]    !== ')')
			return \false;
		$this->tokens[$this->i][1] = \var_export(
			\htmlspecialchars(
				\stripslashes(\substr($this->tokens[$this->i][1], 1, -1)),
				$this->tokens[$this->i + 2][1]
			),
			\true
		);
		unset($this->tokens[$this->i - 2]);
		unset($this->tokens[$this->i - 1]);
		unset($this->tokens[++$this->i]);
		unset($this->tokens[++$this->i]);
		unset($this->tokens[++$this->i]);
		return \true;
	}
	protected function skipPast($token)
	{
		return ($this->skipTo($token) && ++$this->i < $this->cnt);
	}
	protected function skipTo($token)
	{
		while (++$this->i < $this->cnt)
			if ($this->tokens[$this->i] === $token)
				return \true;
		return \false;
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RendererGenerators\PHP;
use RuntimeException;
use s9e\TextFormatter\Configurator\Helpers\RegexpBuilder;
class Quick
{
	public static function getSource(array $compiledTemplates)
	{
		$map = array();
		$tagNames = array();
		$unsupported = array();
		foreach ($compiledTemplates as $tagName => $php)
		{
			if (\preg_match('(^(?:br|[ieps])$)', $tagName))
				continue;
			$rendering = self::getRenderingStrategy($php);
			if ($rendering === \false)
			{
				$unsupported[] = $tagName;
				continue;
			}
			foreach ($rendering as $i => $_562c18b7)
			{
				list($strategy, $replacement) = $_562c18b7;
				$match = (($i) ? '/' : '') . $tagName;
				$map[$strategy][$match] = $replacement;
			}
			if (!isset($rendering[1]))
				$tagNames[] = $tagName;
		}
		$php = array();
		if (isset($map['static']))
			$php[] = '	private static $static=' . self::export($map['static']) . ';';
		if (isset($map['dynamic']))
			$php[] = '	private static $dynamic=' . self::export($map['dynamic']) . ';';
		if (isset($map['php']))
		{
			list($quickBranches, $quickSource) = self::generateBranchTable('$qb', $map['php']);
			$php[] = '	private static $attributes;';
			$php[] = '	private static $quickBranches=' . self::export($quickBranches) . ';';
		}
		if (!empty($unsupported))
		{
			$regexp = '(<' . RegexpBuilder::fromList($unsupported, array('useLookahead' => \true)) . '[ />])';
			$php[] = '	public $quickRenderingTest=' . \var_export($regexp, \true) . ';';
		}
		$php[] = '';
		$php[] = '	protected function renderQuick($xml)';
		$php[] = '	{';
		$php[] = '		$xml = $this->decodeSMP($xml);';
		if (isset($map['php']))
			$php[] = '		self::$attributes = array();';
		$regexp  = '(<(?:(?!/)(';
		$regexp .= ($tagNames) ? RegexpBuilder::fromList($tagNames) : '(?!)';
		$regexp .= ')(?: [^>]*)?>.*?</\\1|(/?(?!br/|p>)[^ />]+)[^>]*?(/)?)>)s';
		$php[] = '		$html = preg_replace_callback(';
		$php[] = '			' . \var_export($regexp, \true) . ',';
		$php[] = "			array(\$this, 'quick'),";
		$php[] = '			preg_replace(';
		$php[] = "				'(<[eis]>[^<]*</[eis]>)',";
		$php[] = "				'',";
		$php[] = '				substr($xml, 1 + strpos($xml, \'>\'), -4)';
		$php[] = '			)';
		$php[] = '		);';
		$php[] = '';
		$php[] = "		return str_replace('<br/>', '<br>', \$html);";
		$php[] = '	}';
		$php[] = '';
		$php[] = '	protected function quick($m)';
		$php[] = '	{';
		$php[] = '		if (isset($m[2]))';
		$php[] = '		{';
		$php[] = '			$id = $m[2];';
		$php[] = '';
		$php[] = '			if (isset($m[3]))';
		$php[] = '			{';
		$php[] = '				unset($m[3]);';
		$php[] = '';
		$php[] = '				$m[0] = substr($m[0], 0, -2) . \'>\';';
		$php[] = '				$html = $this->quick($m);';
		$php[] = '';
		$php[] = '				$m[0] = \'</\' . $id . \'>\';';
		$php[] = '				$m[2] = \'/\' . $id;';
		$php[] = '				$html .= $this->quick($m);';
		$php[] = '';
		$php[] = '				return $html;';
		$php[] = '			}';
		$php[] = '		}';
		$php[] = '		else';
		$php[] = '		{';
		$php[] = '			$id = $m[1];';
		$php[] = '';
		$php[] = '			$lpos = 1 + strpos($m[0], \'>\');';
		$php[] = '			$rpos = strrpos($m[0], \'<\');';
		$php[] = '			$textContent = substr($m[0], $lpos, $rpos - $lpos);';
		$php[] = '';
		$php[] = '			if (strpos($textContent, \'<\') !== false)';
		$php[] = '			{';
		$php[] = '				throw new \\RuntimeException;';
		$php[] = '			}';
		$php[] = '';
		$php[] = '			$textContent = htmlspecialchars_decode($textContent);';
		$php[] = '		}';
		$php[] = '';
		if (isset($map['static']))
		{
			$php[] = '		if (isset(self::$static[$id]))';
			$php[] = '		{';
			$php[] = '			return self::$static[$id];';
			$php[] = '		}';
			$php[] = '';
		}
		if (isset($map['dynamic']))
		{
			$php[] = '		if (isset(self::$dynamic[$id]))';
			$php[] = '		{';
			$php[] = '			list($match, $replace) = self::$dynamic[$id];';
			$php[] = '			return preg_replace($match, $replace, $m[0], 1);';
			$php[] = '		}';
			$php[] = '';
		}
		if (isset($map['php']))
		{
			$php[] = '		if (!isset(self::$quickBranches[$id]))';
			$php[] = '		{';
		}
		$condition = "\$id[0] === '!' || \$id[0] === '?'";
		if (!empty($unsupported))
		{
			$regexp = '(^/?' . RegexpBuilder::fromList($unsupported) . '$)';
			$condition .= ' || preg_match(' . \var_export($regexp, \true) . ', $id)';
		}
		$php[] = '			if (' . $condition . ')';
		$php[] = '			{';
		$php[] = '				throw new \\RuntimeException;';
		$php[] = '			}';
		$php[] = "			return '';";
		if (isset($map['php']))
		{
			$php[] = '		}';
			$php[] = '';
			$php[] = '		$attributes = array();';
			$php[] = '		if (strpos($m[0], \'="\') !== false)';
			$php[] = '		{';
			$php[] = '			preg_match_all(\'(([^ =]++)="([^"]*))S\', substr($m[0], 0, strpos($m[0], \'>\')), $matches);';
			$php[] = '			foreach ($matches[1] as $i => $attrName)';
			$php[] = '			{';
			$php[] = '				$attributes[$attrName] = $matches[2][$i];';
			$php[] = '			}';
			$php[] = '		}';
			$php[] = '';
			$php[] = '		$qb = self::$quickBranches[$id];';
			$php[] = '		' . $quickSource;
			$php[] = '';
			$php[] = '		return $html;';
		}
		$php[] = '	}';
		return \implode("\n", $php);
	}
	protected static function export(array $arr)
	{
		$exportKeys = (\array_keys($arr) !== \range(0, \count($arr) - 1));
		\ksort($arr);
		$entries = array();
		foreach ($arr as $k => $v)
			$entries[] = (($exportKeys) ? \var_export($k, \true) . '=>' : '')
			           . ((\is_array($v)) ? self::export($v) : \var_export($v, \true));
		return 'array(' . \implode(',', $entries) . ')';
	}
	public static function getRenderingStrategy($php)
	{
		$chunks = \explode('$this->at($node);', $php);
		$renderings = array();
		if (\count($chunks) <= 2)
		{
			foreach ($chunks as $k => $chunk)
			{
				$rendering = self::getStaticRendering($chunk);
				if ($rendering !== \false)
				{
					$renderings[$k] = array('static', $rendering);
					continue;
				}
				if ($k === 0)
				{
					$rendering = self::getDynamicRendering($chunk);
					if ($rendering !== \false)
					{
						$renderings[$k] = array('dynamic', $rendering);
						continue;
					}
				}
				$renderings[$k] = \false;
			}
			if (!\in_array(\false, $renderings, \true))
				return $renderings;
		}
		$phpRenderings = self::getQuickRendering($php);
		if ($phpRenderings === \false)
			return \false;
		foreach ($phpRenderings as $i => $phpRendering)
			if (!isset($renderings[$i]) || $renderings[$i] === \false || \strpos($phpRendering, 'self::$attributes[]') !== \false)
				$renderings[$i] = array('php', $phpRendering);
		return $renderings;
	}
	protected static function getQuickRendering($php)
	{
		if (\preg_match('(\\$this->at\\((?!\\$node\\);))', $php))
			return \false;
		$tokens   = \token_get_all('<?php ' . $php);
		$tokens[] = array(0, '');
		\array_shift($tokens);
		$cnt = \count($tokens);
		$branch = array(
			'braces'      => -1,
			'branches'    => array(),
			'head'        => '',
			'passthrough' => 0,
			'statement'   => '',
			'tail'        => ''
		);
		$braces = 0;
		$i = 0;
		do
		{
			if ($tokens[$i    ][0] === \T_VARIABLE
			 && $tokens[$i    ][1] === '$this'
			 && $tokens[$i + 1][0] === \T_OBJECT_OPERATOR
			 && $tokens[$i + 2][0] === \T_STRING
			 && $tokens[$i + 2][1] === 'at'
			 && $tokens[$i + 3]    === '('
			 && $tokens[$i + 4][0] === \T_VARIABLE
			 && $tokens[$i + 4][1] === '$node'
			 && $tokens[$i + 5]    === ')'
			 && $tokens[$i + 6]    === ';')
			{
				if (++$branch['passthrough'] > 1)
					return \false;
				$i += 6;
				continue;
			}
			$key = ($branch['passthrough']) ? 'tail' : 'head';
			$branch[$key] .= (\is_array($tokens[$i])) ? $tokens[$i][1] : $tokens[$i];
			if ($tokens[$i] === '{')
			{
				++$braces;
				continue;
			}
			if ($tokens[$i] === '}')
			{
				--$braces;
				if ($branch['braces'] === $braces)
				{
					$branch[$key] = \substr($branch[$key], 0, -1);
					$branch =& $branch['parent'];
					$j = $i;
					while ($tokens[++$j][0] === \T_WHITESPACE);
					if ($tokens[$j][0] !== \T_ELSEIF
					 && $tokens[$j][0] !== \T_ELSE)
					{
						$passthroughs = self::getBranchesPassthrough($branch['branches']);
						if ($passthroughs === array(0))
						{
							foreach ($branch['branches'] as $child)
								$branch['head'] .= $child['statement'] . '{' . $child['head'] . '}';
							$branch['branches'] = array();
							continue;
						}
						if ($passthroughs === array(1))
						{
							++$branch['passthrough'];
							continue;
						}
						return \false;
					}
				}
				continue;
			}
			if ($branch['passthrough'])
				continue;
			if ($tokens[$i][0] === \T_IF
			 || $tokens[$i][0] === \T_ELSEIF
			 || $tokens[$i][0] === \T_ELSE)
			{
				$branch[$key] = \substr($branch[$key], 0, -\strlen($tokens[$i][1]));
				$branch['branches'][] = array(
					'braces'      => $braces,
					'branches'    => array(),
					'head'        => '',
					'parent'      => &$branch,
					'passthrough' => 0,
					'statement'   => '',
					'tail'        => ''
				);
				$branch =& $branch['branches'][\count($branch['branches']) - 1];
				do
				{
					$branch['statement'] .= (\is_array($tokens[$i])) ? $tokens[$i][1] : $tokens[$i];
				}
				while ($tokens[++$i] !== '{');
				++$braces;
			}
		}
		while (++$i < $cnt);
		list($head, $tail) = self::buildPHP($branch['branches']);
		$head  = $branch['head'] . $head;
		$tail .= $branch['tail'];
		self::convertPHP($head, $tail, (bool) $branch['passthrough']);
		if (\preg_match('((?<!-)->(?!params\\[))', $head . $tail))
			return \false;
		return ($branch['passthrough']) ? array($head, $tail) : array($head);
	}
	protected static function convertPHP(&$head, &$tail, $passthrough)
	{
		$saveAttributes = (bool) \preg_match('(\\$node->(?:get|has)Attribute)', $tail);
		\preg_match_all(
			"(\\\$node->getAttribute\\('([^']+)'\\))",
			\preg_replace_callback(
				'(if\\(\\$node->hasAttribute\\(([^\\)]+)[^}]+)',
				function ($m)
				{
					return \str_replace('$node->getAttribute(' . $m[1] . ')', '', $m[0]);
				},
				$head . $tail
			),
			$matches
		);
		$attrNames = \array_unique($matches[1]);
		self::replacePHP($head);
		self::replacePHP($tail);
		if (!$passthrough)
			$head = \str_replace('$node->textContent', '$textContent', $head);
		if (!empty($attrNames))
		{
			\ksort($attrNames);
			$head = "\$attributes+=array('" . \implode("'=>null,'", $attrNames) . "'=>null);" . $head;
		}
		if ($saveAttributes)
		{
			if (\strpos($head, '$html') === \false)
				$head .= "\$html='';";
			$head .= 'self::$attributes[]=$attributes;';
			$tail  = '$attributes=array_pop(self::$attributes);' . $tail;
		}
	}
	protected static function replacePHP(&$php)
	{
		if ($php === '')
			return;
		$php = \str_replace('$this->out', '$html', $php);
		$getAttribute = "\\\$node->getAttribute\\(('[^']+')\\)";
		$php = \preg_replace(
			'(htmlspecialchars\\(' . $getAttribute . ',' . \ENT_NOQUOTES . '\\))',
			"str_replace('&quot;','\"',\$attributes[\$1])",
			$php
		);
		$php = \preg_replace(
			'(htmlspecialchars\\(' . $getAttribute . ',' . \ENT_COMPAT . '\\))',
			'$attributes[$1]',
			$php
		);
		$php = \preg_replace(
			'(htmlspecialchars\\(strtr\\(' . $getAttribute . ",('[^\"&\\\\';<>aglmopqtu]+'),('[^\"&\\\\'<>]+')\\)," . \ENT_COMPAT . '\\))',
			'strtr($attributes[$1],$2,$3)',
			$php
		);
		$php = \preg_replace(
			'(' . $getAttribute . '(!?=+)' . $getAttribute . ')',
			'$attributes[$1]$2$attributes[$3]',
			$php
		);
		$php = \preg_replace_callback(
			'(' . $getAttribute . "===('.*?(?<!\\\\)(?:\\\\\\\\)*'))s",
			function ($m)
			{
				return '$attributes[' . $m[1] . ']===' . \htmlspecialchars($m[2], \ENT_COMPAT);
			},
			$php
		);
		$php = \preg_replace_callback(
			"(('.*?(?<!\\\\)(?:\\\\\\\\)*')===" . $getAttribute . ')s',
			function ($m)
			{
				return \htmlspecialchars($m[1], \ENT_COMPAT) . '===$attributes[' . $m[2] . ']';
			},
			$php
		);
		$php = \preg_replace_callback(
			'(strpos\\(' . $getAttribute . ",('.*?(?<!\\\\)(?:\\\\\\\\)*')\\)([!=]==(?:0|false)))s",
			function ($m)
			{
				return 'strpos($attributes[' . $m[1] . "]," . \htmlspecialchars($m[2], \ENT_COMPAT) . ')' . $m[3];
			},
			$php
		);
		$php = \preg_replace_callback(
			"(strpos\\(('.*?(?<!\\\\)(?:\\\\\\\\)*')," . $getAttribute . '\\)([!=]==(?:0|false)))s',
			function ($m)
			{
				return 'strpos(' . \htmlspecialchars($m[1], \ENT_COMPAT) . ',$attributes[' . $m[2] . '])' . $m[3];
			},
			$php
		);
		$php = \preg_replace(
			'(' . $getAttribute . '(?=(?:==|[-+*])\\d+))',
			'$attributes[$1]',
			$php
		);
		$php = \preg_replace(
			'((?<!\\w)(\\d+(?:==|[-+*]))' . $getAttribute . ')',
			'$1$attributes[$2]',
			$php
		);
		$php = \preg_replace(
			"(empty\\(\\\$node->getAttribute\\(('[^']+')\\)\\))",
			'empty($attributes[$1])',
			$php
		);
		$php = \preg_replace(
			"(\\\$node->hasAttribute\\(('[^']+')\\))",
			'isset($attributes[$1])',
			$php
		);
		$php = \preg_replace(
			"(\\\$node->getAttribute\\(('[^']+')\\))",
			'htmlspecialchars_decode($attributes[$1])',
			$php
		);
		if (\substr($php, 0, 7) === '$html.=')
			$php = '$html=' . \substr($php, 7);
		else
			$php = "\$html='';" . $php;
	}
	protected static function buildPHP(array $branches)
	{
		$return = array('', '');
		foreach ($branches as $branch)
		{
			$return[0] .= $branch['statement'] . '{' . $branch['head'];
			$return[1] .= $branch['statement'] . '{';
			if ($branch['branches'])
			{
				list($head, $tail) = self::buildPHP($branch['branches']);
				$return[0] .= $head;
				$return[1] .= $tail;
			}
			$return[0] .= '}';
			$return[1] .= $branch['tail'] . '}';
		}
		return $return;
	}
	protected static function getBranchesPassthrough(array $branches)
	{
		$values = array();
		foreach ($branches as $branch)
			$values[] = $branch['passthrough'];
		if ($branch['statement'] !== 'else')
			$values[] = 0;
		return \array_unique($values);
	}
	protected static function getDynamicRendering($php)
	{
		$rendering = '';
		$literal   = "(?<literal>'((?>[^'\\\\]+|\\\\['\\\\])*)')";
		$attribute = "(?<attribute>htmlspecialchars\\(\\\$node->getAttribute\\('([^']+)'\\),2\\))";
		$value     = "(?<value>$literal|$attribute)";
		$output    = "(?<output>\\\$this->out\\.=$value(?:\\.(?&value))*;)";
		$copyOfAttribute = "(?<copyOfAttribute>if\\(\\\$node->hasAttribute\\('([^']+)'\\)\\)\\{\\\$this->out\\.=' \\g-1=\"'\\.htmlspecialchars\\(\\\$node->getAttribute\\('\\g-1'\\),2\\)\\.'\"';\\})";
		$regexp = '(^(' . $output . '|' . $copyOfAttribute . ')*$)';
		if (!\preg_match($regexp, $php, $m))
			return \false;
		$copiedAttributes = array();
		$usedAttributes = array();
		$regexp = '(' . $output . '|' . $copyOfAttribute . ')A';
		$offset = 0;
		while (\preg_match($regexp, $php, $m, 0, $offset))
			if ($m['output'])
			{
				$offset += 12;
				while (\preg_match('(' . $value . ')A', $php, $m, 0, $offset))
				{
					if ($m['literal'])
					{
						$str = \stripslashes(\substr($m[0], 1, -1));
						$rendering .= \preg_replace('([\\\\$](?=\\d))', '\\\\$0', $str);
					}
					else
					{
						$attrName = \end($m);
						if (!isset($usedAttributes[$attrName]))
							$usedAttributes[$attrName] = \uniqid($attrName, \true);
						$rendering .= $usedAttributes[$attrName];
					}
					$offset += 1 + \strlen($m[0]);
				}
			}
			else
			{
				$attrName = \end($m);
				if (!isset($copiedAttributes[$attrName]))
					$copiedAttributes[$attrName] = \uniqid($attrName, \true);
				$rendering .= $copiedAttributes[$attrName];
				$offset += \strlen($m[0]);
			}
		$attrNames = \array_keys($copiedAttributes + $usedAttributes);
		\sort($attrNames);
		$remainingAttributes = \array_combine($attrNames, $attrNames);
		$regexp = '(^[^ ]+';
		$index  = 0;
		foreach ($attrNames as $attrName)
		{
			$regexp .= '(?> (?!' . RegexpBuilder::fromList($remainingAttributes) . '=)[^=]+="[^"]*")*';
			unset($remainingAttributes[$attrName]);
			$regexp .= '(';
			if (isset($copiedAttributes[$attrName]))
				self::replacePlaceholder($rendering, $copiedAttributes[$attrName], ++$index);
			else
				$regexp .= '?>';
			$regexp .= ' ' . $attrName . '="';
			if (isset($usedAttributes[$attrName]))
			{
				$regexp .= '(';
				self::replacePlaceholder($rendering, $usedAttributes[$attrName], ++$index);
			}
			$regexp .= '[^"]*';
			if (isset($usedAttributes[$attrName]))
				$regexp .= ')';
			$regexp .= '")?';
		}
		$regexp .= '.*)s';
		return array($regexp, $rendering);
	}
	protected static function getStaticRendering($php)
	{
		if ($php === '')
			return '';
		$regexp = "(^\\\$this->out\.='((?>[^'\\\\]+|\\\\['\\\\])*)';\$)";
		if (!\preg_match($regexp, $php, $m))
			return \false;
		return \stripslashes($m[1]);
	}
	protected static function replacePlaceholder(&$str, $uniqid, $index)
	{
		$str = \preg_replace_callback(
			'(' . \preg_quote($uniqid) . '(.))',
			function ($m) use ($index)
			{
				if (\is_numeric($m[1]))
					return '${' . $index . '}' . $m[1];
				else
					return '$' . $index . $m[1];
			},
			$str
		);
	}
	public static function generateConditionals($expr, array $statements)
	{
		$keys = \array_keys($statements);
		$cnt  = \count($statements);
		$min  = (int) $keys[0];
		$max  = (int) $keys[$cnt - 1];
		if ($cnt <= 4)
		{
			if ($cnt === 1)
				return \end($statements);
			$php = '';
			$k = $min;
			do
			{
				$php .= 'if(' . $expr . '===' . $k . '){' . $statements[$k] . '}else';
			}
			while (++$k < $max);
			$php .= '{' . $statements[$max] . '}';
			
			return $php;
		}
		$cutoff = \ceil($cnt / 2);
		$chunks = \array_chunk($statements, $cutoff, \true);
		return 'if(' . $expr . '<' . \key($chunks[1]) . '){' . self::generateConditionals($expr, \array_slice($statements, 0, $cutoff, \true)) . '}else' . self::generateConditionals($expr, \array_slice($statements, $cutoff, \null, \true));
	}
	public static function generateBranchTable($expr, array $statements)
	{
		$branchTable = array();
		$branchIds = array();
		\ksort($statements);
		foreach ($statements as $value => $statement)
		{
			if (!isset($branchIds[$statement]))
				$branchIds[$statement] = \count($branchIds);
			$branchTable[$value] = $branchIds[$statement];
		}
		return array($branchTable, self::generateConditionals($expr, \array_keys($branchIds)));
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RendererGenerators\PHP;
use DOMElement;
use DOMXPath;
use RuntimeException;
use s9e\TextFormatter\Configurator\Helpers\AVTHelper;
use s9e\TextFormatter\Configurator\Helpers\TemplateParser;
class Serializer
{
	public $branchTableThreshold = 8;
	public $branchTables = array();
	public $convertor;
	public $useMultibyteStringFunctions = \false;
	public function __construct()
	{
		$this->convertor = new XPathConvertor;
	}
	protected function convertAttributeValueTemplate($attrValue)
	{
		$phpExpressions = array();
		foreach (AVTHelper::parse($attrValue) as $token)
			if ($token[0] === 'literal')
				$phpExpressions[] = \var_export($token[1], \true);
			else
				$phpExpressions[] = $this->convertXPath($token[1]);
		return \implode('.', $phpExpressions);
	}
	public function convertCondition($expr)
	{
		$this->convertor->useMultibyteStringFunctions = $this->useMultibyteStringFunctions;
		return $this->convertor->convertCondition($expr);
	}
	public function convertXPath($expr)
	{
		$this->convertor->useMultibyteStringFunctions = $this->useMultibyteStringFunctions;
		return $this->convertor->convertXPath($expr);
	}
	protected function escapeLiteral($text, $context)
	{
		if ($context === 'raw')
			return $text;
		$escapeMode = ($context === 'attribute') ? \ENT_COMPAT : \ENT_NOQUOTES;
		return \htmlspecialchars($text, $escapeMode);
	}
	protected function escapePHPOutput($php, $context)
	{
		if ($context === 'raw')
			return $php;
		$escapeMode = ($context === 'attribute') ? \ENT_COMPAT : \ENT_NOQUOTES;
		return 'htmlspecialchars(' . $php . ',' . $escapeMode . ')';
	}
	protected function serializeApplyTemplates(DOMElement $applyTemplates)
	{
		$php = '$this->at($node';
		if ($applyTemplates->hasAttribute('select'))
			$php .= ',' . \var_export($applyTemplates->getAttribute('select'), \true);
		$php .= ');';
		return $php;
	}
	protected function serializeAttribute(DOMElement $attribute)
	{
		$attrName = $attribute->getAttribute('name');
		$phpAttrName = $this->convertAttributeValueTemplate($attrName);
		$phpAttrName = 'htmlspecialchars(' . $phpAttrName . ',' . \ENT_QUOTES . ')';
		return "\$this->out.=' '." . $phpAttrName . ".'=\"';"
		     . $this->serializeChildren($attribute)
		     . "\$this->out.='\"';";
	}
	public function serialize(DOMElement $ir)
	{
		$this->branchTables = array();
		return $this->serializeChildren($ir);
	}
	protected function serializeChildren(DOMElement $ir)
	{
		$php = '';
		foreach ($ir->childNodes as $node)
		{
			$methodName = 'serialize' . \ucfirst($node->localName);
			$php .= $this->$methodName($node);
		}
		return $php;
	}
	protected function serializeCloseTag(DOMElement $closeTag)
	{
		$php = '';
		$id  = $closeTag->getAttribute('id');
		if ($closeTag->hasAttribute('check'))
			$php .= 'if(!isset($t' . $id . ')){';
		if ($closeTag->hasAttribute('set'))
			$php .= '$t' . $id . '=1;';
		$xpath   = new DOMXPath($closeTag->ownerDocument);
		$element = $xpath->query('ancestor::element[@id="' . $id . '"]', $closeTag)->item(0);
		if (!($element instanceof DOMElement))
			throw new RuntimeException;
		$php .= "\$this->out.='>';";
		if ($element->getAttribute('void') === 'maybe')
			$php .= 'if(!$v' . $id . '){';
		if ($closeTag->hasAttribute('check'))
			$php .= '}';
		return $php;
	}
	protected function serializeComment(DOMElement $comment)
	{
		return "\$this->out.='<!--';"
		     . $this->serializeChildren($comment)
		     . "\$this->out.='-->';";
	}
	protected function serializeCopyOfAttributes(DOMElement $copyOfAttributes)
	{
		return 'foreach($node->attributes as $attribute){'
		     . "\$this->out.=' ';\$this->out.=\$attribute->name;\$this->out.='=\"';\$this->out.=htmlspecialchars(\$attribute->value," . \ENT_COMPAT . ");\$this->out.='\"';"
		     . '}';
	}
	protected function serializeElement(DOMElement $element)
	{
		$php     = '';
		$elName  = $element->getAttribute('name');
		$id      = $element->getAttribute('id');
		$isVoid  = $element->getAttribute('void');
		$isDynamic = (bool) (\strpos($elName, '{') !== \false);
		$phpElName = $this->convertAttributeValueTemplate($elName);
		$phpElName = 'htmlspecialchars(' . $phpElName . ',' . \ENT_QUOTES . ')';
		if ($isDynamic)
		{
			$varName = '$e' . $id;
			$php .= $varName . '=' . $phpElName . ';';
			$phpElName = $varName;
		}
		if ($isVoid === 'maybe')
			$php .= '$v' . $id . '=preg_match(' . \var_export(TemplateParser::$voidRegexp, \true) . ',' . $phpElName . ');';
		$php .= "\$this->out.='<'." . $phpElName . ';';
		$php .= $this->serializeChildren($element);
		if ($isVoid !== 'yes')
			$php .= "\$this->out.='</'." . $phpElName . ".'>';";
		if ($isVoid === 'maybe')
			$php .= '}';
		return $php;
	}
	protected function serializeHash(DOMElement $switch)
	{
		$statements = array();
		foreach ($switch->getElementsByTagName('case') as $case)
		{
			if (!$case->parentNode->isSameNode($switch))
				continue;
			if ($case->hasAttribute('branch-values'))
			{
				$php = $this->serializeChildren($case);
				foreach (\unserialize($case->getAttribute('branch-values')) as $value)
					$statements[$value] = $php;
			}
		}
		if (!isset($case))
			throw new RuntimeException;
		list($branchTable, $php) = Quick::generateBranchTable('$n', $statements);
		$varName = 'bt' . \sprintf('%08X', \crc32(\serialize($branchTable)));
		$expr = 'self::$' . $varName . '[' . $this->convertXPath($switch->getAttribute('branch-key')) . ']';
		$php = 'if(isset(' . $expr . ')){$n=' . $expr . ';' . $php . '}';
		if (!$case->hasAttribute('branch-values'))
			$php .= 'else{' . $this->serializeChildren($case) . '}';
		$this->branchTables[$varName] = $branchTable;
		return $php;
	}
	protected function serializeOutput(DOMElement $output)
	{
		$context = $output->getAttribute('escape');
		$php = '$this->out.=';
		if ($output->getAttribute('type') === 'xpath')
			$php .= $this->escapePHPOutput($this->convertXPath($output->textContent), $context);
		else
			$php .= \var_export($this->escapeLiteral($output->textContent, $context), \true);
		$php .= ';';
		return $php;
	}
	protected function serializeSwitch(DOMElement $switch)
	{
		if ($switch->hasAttribute('branch-key')
		 && $switch->childNodes->length >= $this->branchTableThreshold)
			return $this->serializeHash($switch);
		$php  = '';
		$else = '';
		foreach ($switch->getElementsByTagName('case') as $case)
		{
			if (!$case->parentNode->isSameNode($switch))
				continue;
			if ($case->hasAttribute('test'))
				$php .= $else . 'if(' . $this->convertCondition($case->getAttribute('test')) . ')';
			else
				$php .= 'else';
			$else = 'else';
			$php .= '{';
			$php .= $this->serializeChildren($case);
			$php .= '}';
		}
		return $php;
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RendererGenerators\PHP;
use LogicException;
use RuntimeException;
class XPathConvertor
{
	public $pcreVersion;
	protected $regexp;
	public $useMultibyteStringFunctions = \false;
	public function __construct()
	{
		$this->pcreVersion = \PCRE_VERSION;
	}
	public function convertCondition($expr)
	{
		$expr = \trim($expr);
		if (\preg_match('#^@([-\\w]+)$#', $expr, $m))
			return '$node->hasAttribute(' . \var_export($m[1], \true) . ')';
		if (\preg_match('#^not\\(@([-\\w]+)\\)$#', $expr, $m))
			return '!$node->hasAttribute(' . \var_export($m[1], \true) . ')';
		if (\preg_match('#^\\$(\\w+)$#', $expr, $m))
			return '!empty($this->params[' . \var_export($m[1], \true) . '])';
		if (\preg_match('#^not\\(\\$(\\w+)\\)$#', $expr, $m))
			return 'empty($this->params[' . \var_export($m[1], \true) . '])';
		if (\preg_match('#^([$@][-\\w]+)\\s*([<>])\\s*(\\d+)$#', $expr, $m))
			return $this->convertXPath($m[1]) . $m[2] . $m[3];
		if (!\preg_match('#[=<>]|\\bor\\b|\\band\\b|^[-\\w]+\\s*\\(#', $expr))
			$expr = 'boolean(' . $expr . ')';
		return $this->convertXPath($expr);
	}
	public function convertXPath($expr)
	{
		$expr = \trim($expr);
		$this->generateXPathRegexp();
		if (\preg_match($this->regexp, $expr, $m))
		{
			$methodName = \null;
			foreach ($m as $k => $v)
			{
				if (\is_numeric($k) || $v === '' || !\method_exists($this, $k))
					continue;
				$methodName = $k;
				break;
			}
			if (isset($methodName))
			{
				$args = array($m[$methodName]);
				$i = 0;
				while (isset($m[$methodName . $i]))
				{
					$args[$i] = $m[$methodName . $i];
					++$i;
				}
				return \call_user_func_array(array($this, $methodName), $args);
			}
		}
		if (!\preg_match('#[=<>]|\\bor\\b|\\band\\b|^[-\\w]+\\s*\\(#', $expr))
			$expr = 'string(' . $expr . ')';
		return '$this->xpath->evaluate(' . $this->exportXPath($expr) . ',$node)';
	}
	protected function attr($attrName)
	{
		return '$node->getAttribute(' . \var_export($attrName, \true) . ')';
	}
	protected function dot()
	{
		return '$node->textContent';
	}
	protected function param($paramName)
	{
		return '$this->params[' . \var_export($paramName, \true) . ']';
	}
	protected function string($string)
	{
		return \var_export(\substr($string, 1, -1), \true);
	}
	protected function lname()
	{
		return '$node->localName';
	}
	protected function name()
	{
		return '$node->nodeName';
	}
	protected function number($number)
	{
		return "'" . $number . "'";
	}
	protected function strlen($expr)
	{
		if ($expr === '')
			$expr = '.';
		$php = $this->convertXPath($expr);
		return ($this->useMultibyteStringFunctions)
			? 'mb_strlen(' . $php . ",'utf-8')"
			: "strlen(preg_replace('(.)us','.'," . $php . '))';
	}
	protected function contains($haystack, $needle)
	{
		return '(strpos(' . $this->convertXPath($haystack) . ',' . $this->convertXPath($needle) . ')!==false)';
	}
	protected function startswith($string, $substring)
	{
		return '(strpos(' . $this->convertXPath($string) . ',' . $this->convertXPath($substring) . ')===0)';
	}
	protected function not($expr)
	{
		return '!(' . $this->convertCondition($expr) . ')';
	}
	protected function notcontains($haystack, $needle)
	{
		return '(strpos(' . $this->convertXPath($haystack) . ',' . $this->convertXPath($needle) . ')===false)';
	}
	protected function substr($exprString, $exprPos, $exprLen = \null)
	{
		if (!$this->useMultibyteStringFunctions)
		{
			$expr = 'substring(' . $exprString . ',' . $exprPos;
			if (isset($exprLen))
				$expr .= ',' . $exprLen;
			$expr .= ')';
			return '$this->xpath->evaluate(' . $this->exportXPath($expr) . ',$node)';
		}
		$php = 'mb_substr(' . $this->convertXPath($exprString) . ',';
		if (\is_numeric($exprPos))
			$php .= \max(0, $exprPos - 1);
		else
			$php .= 'max(0,' . $this->convertXPath($exprPos) . '-1)';
		$php .= ',';
		if (isset($exprLen))
			if (\is_numeric($exprLen))
				if (\is_numeric($exprPos) && $exprPos < 1)
					$php .= \max(0, $exprPos + $exprLen - 1);
				else
					$php .= \max(0, $exprLen);
			else
				$php .= 'max(0,' . $this->convertXPath($exprLen) . ')';
		else
			$php .= 0x7fffffe;
		$php .= ",'utf-8')";
		return $php;
	}
	protected function substringafter($expr, $str)
	{
		return 'substr(strstr(' . $this->convertXPath($expr) . ',' . $this->convertXPath($str) . '),' . (\strlen($str) - 2) . ')';
	}
	protected function substringbefore($expr1, $expr2)
	{
		return 'strstr(' . $this->convertXPath($expr1) . ',' . $this->convertXPath($expr2) . ',true)';
	}
	protected function cmp($expr1, $operator, $expr2)
	{
		$operands  = array();
		$operators = array(
			'='  => '===',
			'!=' => '!==',
			'>'  => '>',
			'>=' => '>=',
			'<'  => '<',
			'<=' => '<='
		);
		foreach (array($expr1, $expr2) as $expr)
			if (\is_numeric($expr))
			{
				$operators['=']  = '==';
				$operators['!='] = '!=';
				$operands[] = \preg_replace('(^0(.+))', '$1', $expr);
			}
			else
				$operands[] = $this->convertXPath($expr);
		return \implode($operators[$operator], $operands);
	}
	protected function bool($expr1, $operator, $expr2)
	{
		$operators = array(
			'and' => '&&',
			'or'  => '||'
		);
		return $this->convertCondition($expr1) . $operators[$operator] . $this->convertCondition($expr2);
	}
	protected function parens($expr)
	{
		return '(' . $this->convertXPath($expr) . ')';
	}
	protected function translate($str, $from, $to)
	{
		\preg_match_all('(.)su', \substr($from, 1, -1), $matches);
		$from = $matches[0];
		\preg_match_all('(.)su', \substr($to, 1, -1), $matches);
		$to = $matches[0];
		if (\count($to) > \count($from))
			$to = \array_slice($to, 0, \count($from));
		else
			while (\count($from) > \count($to))
				$to[] = '';
		$from = \array_unique($from);
		$to   = \array_intersect_key($to, $from);
		$php = 'strtr(' . $this->convertXPath($str) . ',';
		if (array(1) === \array_unique(\array_map('strlen', $from))
		 && array(1) === \array_unique(\array_map('strlen', $to)))
			$php .= \var_export(\implode('', $from), \true) . ',' . \var_export(\implode('', $to), \true);
		else
		{
			$php .= 'array(';
			$cnt = \count($from);
			for ($i = 0; $i < $cnt; ++$i)
			{
				if ($i)
					$php .= ',';
				$php .= \var_export($from[$i], \true) . '=>' . \var_export($to[$i], \true);
			}
			$php .= ')';
		}
		$php .= ')';
		return $php;
	}
	protected function math($expr1, $operator, $expr2)
	{
		if (!\is_numeric($expr1))
			$expr1 = $this->convertXPath($expr1);
		if (!\is_numeric($expr2))
			$expr2 = $this->convertXPath($expr2);
		if ($operator === 'div')
			$operator = '/';
		return $expr1 . $operator . $expr2;
	}
	protected function exportXPath($expr)
	{
		$phpTokens = array();
		$pos = 0;
		$len = \strlen($expr);
		while ($pos < $len)
		{
			if ($expr[$pos] === "'" || $expr[$pos] === '"')
			{
				$nextPos = \strpos($expr, $expr[$pos], 1 + $pos);
				if ($nextPos === \false)
					throw new RuntimeException('Unterminated string literal in XPath expression ' . \var_export($expr, \true));
				$phpTokens[] = \var_export(\substr($expr, $pos, $nextPos + 1 - $pos), \true);
				$pos = $nextPos + 1;
				continue;
			}
			if ($expr[$pos] === '$' && \preg_match('/\\$(\\w+)/', $expr, $m, 0, $pos))
			{
				$phpTokens[] = '$this->getParamAsXPath(' . \var_export($m[1], \true) . ')';
				$pos += \strlen($m[0]);
				continue;
			}
			$spn = \strcspn($expr, '\'"$', $pos);
			if ($spn)
			{
				$phpTokens[] = \var_export(\substr($expr, $pos, $spn), \true);
				$pos += $spn;
			}
		}
		return \implode('.', $phpTokens);
	}
	protected function generateXPathRegexp()
	{
		if (isset($this->regexp))
			return;
		$patterns = array(
			'attr'      => array('@', '(?<attr0>[-\\w]+)'),
			'dot'       => '\\.',
			'name'      => 'name\\(\\)',
			'lname'     => 'local-name\\(\\)',
			'param'     => array('\\$', '(?<param0>\\w+)'),
			'string'    => '"[^"]*"|\'[^\']*\'',
			'number'    => array('-?', '\\d++'),
			'strlen'    => array('string-length', '\\(', '(?<strlen0>(?&value)?)', '\\)'),
			'contains'  => array(
				'contains',
				'\\(',
				'(?<contains0>(?&value))',
				',',
				'(?<contains1>(?&value))',
				'\\)'
			),
			'translate' => array(
				'translate',
				'\\(',
				'(?<translate0>(?&value))',
				',',
				'(?<translate1>(?&string))',
				',',
				'(?<translate2>(?&string))',
				'\\)'
			),
			'substr' => array(
				'substring',
				'\\(',
				'(?<substr0>(?&value))',
				',',
				'(?<substr1>(?&value))',
				'(?:, (?<substr2>(?&value)))?',
				'\\)'
			),
			'substringafter' => array(
				'substring-after',
				'\\(',
				'(?<substringafter0>(?&value))',
				',',
				'(?<substringafter1>(?&string))',
				'\\)'
			),
			'substringbefore' => array(
				'substring-before',
				'\\(',
				'(?<substringbefore0>(?&value))',
				',',
				'(?<substringbefore1>(?&value))',
				'\\)'
			),
			'startswith' => array(
				'starts-with',
				'\\(',
				'(?<startswith0>(?&value))',
				',',
				'(?<startswith1>(?&value))',
				'\\)'
			),
			'math' => array(
				'(?<math0>(?&attr)|(?&number)|(?&param))',
				'(?<math1>[-+*]|div)',
				'(?<math2>(?&math)|(?&math0))'
			),
			'notcontains' => array(
				'not',
				'\\(',
				'contains',
				'\\(',
				'(?<notcontains0>(?&value))',
				',',
				'(?<notcontains1>(?&value))',
				'\\)',
				'\\)'
			)
		);
		$exprs = array();
		if (\version_compare($this->pcreVersion, '8.13', '>='))
		{
			$exprs[] = '(?<cmp>(?<cmp0>(?&value)) (?<cmp1>!?=) (?<cmp2>(?&value)))';
			$exprs[] = '(?<parens>\\( (?<parens0>(?&bool)|(?&cmp)|(?&math)) \\))';
			$exprs[] = '(?<bool>(?<bool0>(?&cmp)|(?&not)|(?&value)|(?&parens)) (?<bool1>and|or) (?<bool2>(?&bool)|(?&cmp)|(?&not)|(?&value)|(?&parens)))';
			$exprs[] = '(?<not>not \\( (?<not0>(?&bool)|(?&value)) \\))';
			$patterns['math'][0] = \str_replace('))', ')|(?&parens))', $patterns['math'][0]);
			$patterns['math'][1] = \str_replace('))', ')|(?&parens))', $patterns['math'][1]);
		}
		$valueExprs = array();
		foreach ($patterns as $name => $pattern)
		{
			if (\is_array($pattern))
				$pattern = \implode(' ', $pattern);
			if (\strpos($pattern, '?&') === \false || \version_compare($this->pcreVersion, '8.13', '>='))
				$valueExprs[] = '(?<' . $name . '>' . $pattern . ')';
		}
		\array_unshift($exprs, '(?<value>' . \implode('|', $valueExprs) . ')');

		$regexp = '#^(?:' . \implode('|', $exprs) . ')$#S';
		$regexp = \str_replace(' ', '\\s*', $regexp);
		$this->regexp = $regexp;
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator;
use InvalidArgumentException;
use ReflectionClass;
use RuntimeException;
use Traversable;
use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Configurator\Collections\Collection;
use s9e\TextFormatter\Configurator\Collections\NormalizedCollection;
use s9e\TextFormatter\Configurator\Collections\TemplateParameterCollection;
use s9e\TextFormatter\Configurator\RendererGenerator;
use s9e\TextFormatter\Configurator\Traits\Configurable;
class Rendering
{
	public function __get($propName)
	{
		$methodName = 'get' . \ucfirst($propName);
		if (\method_exists($this, $methodName))
			return $this->$methodName();
		if (!\property_exists($this, $propName))
			throw new RuntimeException("Property '" . $propName . "' does not exist");
		return $this->$propName;
	}
	public function __set($propName, $propValue)
	{
		$methodName = 'set' . \ucfirst($propName);
		if (\method_exists($this, $methodName))
		{
			$this->$methodName($propValue);
			return;
		}
		if (!isset($this->$propName))
		{
			$this->$propName = $propValue;
			return;
		}
		if ($this->$propName instanceof NormalizedCollection)
		{
			if (!\is_array($propValue)
			 && !($propValue instanceof Traversable))
				throw new InvalidArgumentException("Property '" . $propName . "' expects an array or a traversable object to be passed");
			$this->$propName->clear();
			foreach ($propValue as $k => $v)
				$this->$propName->set($k, $v);
			return;
		}
		if (\is_object($this->$propName))
		{
			if (!($propValue instanceof $this->$propName))
				throw new InvalidArgumentException("Cannot replace property '" . $propName . "' of class '" . \get_class($this->$propName) . "' with instance of '" . \get_class($propValue) . "'");
		}
		else
		{
			$oldType = \gettype($this->$propName);
			$newType = \gettype($propValue);
			if ($oldType === 'boolean')
				if ($propValue === 'false')
				{
					$newType   = 'boolean';
					$propValue = \false;
				}
				elseif ($propValue === 'true')
				{
					$newType   = 'boolean';
					$propValue = \true;
				}
			if ($oldType !== $newType)
			{
				$tmp = $propValue;
				\settype($tmp, $oldType);
				\settype($tmp, $newType);
				if ($tmp !== $propValue)
					throw new InvalidArgumentException("Cannot replace property '" . $propName . "' of type " . $oldType . ' with value of type ' . $newType);
				\settype($propValue, $oldType);
			}
		}
		$this->$propName = $propValue;
	}
	public function __isset($propName)
	{
		$methodName = 'isset' . \ucfirst($propName);
		if (\method_exists($this, $methodName))
			return $this->$methodName();
		return isset($this->$propName);
	}
	public function __unset($propName)
	{
		$methodName = 'unset' . \ucfirst($propName);
		if (\method_exists($this, $methodName))
		{
			$this->$methodName();
			return;
		}
		if (!isset($this->$propName))
			return;
		if ($this->$propName instanceof Collection)
		{
			$this->$propName->clear();
			return;
		}
		throw new RuntimeException("Property '" . $propName . "' cannot be unset");
	}
	protected $configurator;
	protected $engine;
	protected $parameters;
	public function __construct(Configurator $configurator)
	{
		$this->configurator = $configurator;
		$this->parameters   = new TemplateParameterCollection;
	}
	public function getAllParameters()
	{
		$params = array();
		foreach ($this->configurator->tags as $tag)
			if (isset($tag->template))
				foreach ($tag->template->getParameters() as $paramName)
					$params[$paramName] = '';
		$params = \iterator_to_array($this->parameters) + $params;
		\ksort($params);
		return $params;
	}
	public function getEngine()
	{
		if (!isset($this->engine))
			$this->setEngine('XSLT');
		return $this->engine;
	}
	public function getRenderer()
	{
		return $this->getEngine()->getRenderer($this);
	}
	public function getTemplates()
	{
		$templates = array(
			'br' => '<br/>',
			'e'  => '',
			'i'  => '',
			'p'  => '<p><xsl:apply-templates/></p>',
			's'  => ''
		);
		foreach ($this->configurator->tags as $tagName => $tag)
			if (isset($tag->template))
				$templates[$tagName] = (string) $tag->template;
		\ksort($templates);
		return $templates;
	}
	public function setEngine($engine)
	{
		if (!($engine instanceof RendererGenerator))
		{
			$className  = 's9e\\TextFormatter\\Configurator\\RendererGenerators\\' . $engine;
			$reflection = new ReflectionClass($className);
			$engine = (\func_num_args() > 1) ? $reflection->newInstanceArgs(\array_slice(\func_get_args(), 1)) : $reflection->newInstance();
		}
		$this->engine = $engine;
		return $engine;
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator;
use ArrayAccess;
use DOMDocument;
use Iterator;
use s9e\TextFormatter\Configurator\Collections\RulesGeneratorList;
use s9e\TextFormatter\Configurator\Collections\TagCollection;
use s9e\TextFormatter\Configurator\Helpers\TemplateForensics;
use s9e\TextFormatter\Configurator\RulesGenerators\Interfaces\BooleanRulesGenerator;
use s9e\TextFormatter\Configurator\RulesGenerators\Interfaces\TargetedRulesGenerator;
use s9e\TextFormatter\Configurator\Traits\CollectionProxy;
class RulesGenerator implements ArrayAccess, Iterator
{
	public function __call($methodName, $args)
	{
		return \call_user_func_array(array($this->collection, $methodName), $args);
	}
	public function offsetExists($offset)
	{
		return isset($this->collection[$offset]);
	}
	public function offsetGet($offset)
	{
		return $this->collection[$offset];
	}
	public function offsetSet($offset, $value)
	{
		$this->collection[$offset] = $value;
	}
	public function offsetUnset($offset)
	{
		unset($this->collection[$offset]);
	}
	public function count()
	{
		return \count($this->collection);
	}
	public function current()
	{
		return $this->collection->current();
	}
	public function key()
	{
		return $this->collection->key();
	}
	public function next()
	{
		return $this->collection->next();
	}
	public function rewind()
	{
		$this->collection->rewind();
	}
	public function valid()
	{
		return $this->collection->valid();
	}
	protected $collection;
	public function __construct()
	{
		$this->collection = new RulesGeneratorList;
		$this->collection->append('AutoCloseIfVoid');
		$this->collection->append('AutoReopenFormattingElements');
		$this->collection->append('BlockElementsFosterFormattingElements');
		$this->collection->append('DisableAutoLineBreaksIfNewLinesArePreserved');
		$this->collection->append('EnforceContentModels');
		$this->collection->append('EnforceOptionalEndTags');
		$this->collection->append('IgnoreTagsInCode');
		$this->collection->append('IgnoreTextIfDisallowed');
		$this->collection->append('IgnoreWhitespaceAroundBlockElements');
		$this->collection->append('TrimFirstLineInCodeBlocks');
	}
	public function getRules(TagCollection $tags, array $options = array())
	{
		$parentHTML = (isset($options['parentHTML'])) ? $options['parentHTML'] : '<div>';
		$rootForensics = $this->generateRootForensics($parentHTML);
		$templateForensics = array();
		foreach ($tags as $tagName => $tag)
		{
			$template = (isset($tag->template)) ? $tag->template : '<xsl:apply-templates/>';
			$templateForensics[$tagName] = new TemplateForensics($template);
		}
		$rules = $this->generateRulesets($templateForensics, $rootForensics);
		unset($rules['root']['autoClose']);
		unset($rules['root']['autoReopen']);
		unset($rules['root']['breakParagraph']);
		unset($rules['root']['closeAncestor']);
		unset($rules['root']['closeParent']);
		unset($rules['root']['fosterParent']);
		unset($rules['root']['ignoreSurroundingWhitespace']);
		unset($rules['root']['isTransparent']);
		unset($rules['root']['requireAncestor']);
		unset($rules['root']['requireParent']);
		return $rules;
	}
	protected function generateRootForensics($html)
	{
		$dom = new DOMDocument;
		$dom->loadHTML($html);
		$body = $dom->getElementsByTagName('body')->item(0);
		$node = $body;
		while ($node->firstChild)
			$node = $node->firstChild;
		$node->appendChild($dom->createElementNS(
			'http://www.w3.org/1999/XSL/Transform',
			'xsl:apply-templates'
		));
		return new TemplateForensics($dom->saveXML($body));
	}
	protected function generateRulesets(array $templateForensics, TemplateForensics $rootForensics)
	{
		$rules = array(
			'root' => $this->generateRuleset($rootForensics, $templateForensics),
			'tags' => array()
		);
		foreach ($templateForensics as $tagName => $src)
			$rules['tags'][$tagName] = $this->generateRuleset($src, $templateForensics);
		return $rules;
	}
	protected function generateRuleset(TemplateForensics $src, array $targets)
	{
		$rules = array();
		foreach ($this->collection as $rulesGenerator)
		{
			if ($rulesGenerator instanceof BooleanRulesGenerator)
				foreach ($rulesGenerator->generateBooleanRules($src) as $ruleName => $bool)
					$rules[$ruleName] = $bool;
			if ($rulesGenerator instanceof TargetedRulesGenerator)
				foreach ($targets as $tagName => $trg)
					foreach ($rulesGenerator->generateTargetedRules($src, $trg) as $ruleName)
						$rules[$ruleName][] = $tagName;
		}
		return $rules;
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RulesGenerators\Interfaces;
use s9e\TextFormatter\Configurator\Helpers\TemplateForensics;
interface BooleanRulesGenerator
{
	public function generateBooleanRules(TemplateForensics $src);
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RulesGenerators\Interfaces;
use s9e\TextFormatter\Configurator\Helpers\TemplateForensics;
interface TargetedRulesGenerator
{
	public function generateTargetedRules(TemplateForensics $src, TemplateForensics $trg);
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator;
use DOMElement;
use s9e\TextFormatter\Configurator\Items\Tag;
abstract class TemplateCheck
{
	const XMLNS_XSL = 'http://www.w3.org/1999/XSL/Transform';
	abstract public function check(DOMElement $template, Tag $tag);
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator;
use ArrayAccess;
use Iterator;
use s9e\TextFormatter\Configurator\Collections\TemplateCheckList;
use s9e\TextFormatter\Configurator\Helpers\TemplateHelper;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\Items\UnsafeTemplate;
use s9e\TextFormatter\Configurator\TemplateChecks\DisallowElementNS;
use s9e\TextFormatter\Configurator\TemplateChecks\DisallowXPathFunction;
use s9e\TextFormatter\Configurator\TemplateChecks\RestrictFlashScriptAccess;
use s9e\TextFormatter\Configurator\Traits\CollectionProxy;
class TemplateChecker implements ArrayAccess, Iterator
{
	public function __call($methodName, $args)
	{
		return \call_user_func_array(array($this->collection, $methodName), $args);
	}
	public function offsetExists($offset)
	{
		return isset($this->collection[$offset]);
	}
	public function offsetGet($offset)
	{
		return $this->collection[$offset];
	}
	public function offsetSet($offset, $value)
	{
		$this->collection[$offset] = $value;
	}
	public function offsetUnset($offset)
	{
		unset($this->collection[$offset]);
	}
	public function count()
	{
		return \count($this->collection);
	}
	public function current()
	{
		return $this->collection->current();
	}
	public function key()
	{
		return $this->collection->key();
	}
	public function next()
	{
		return $this->collection->next();
	}
	public function rewind()
	{
		$this->collection->rewind();
	}
	public function valid()
	{
		return $this->collection->valid();
	}
	protected $collection;
	protected $disabled = \false;
	public function __construct()
	{
		$this->collection = new TemplateCheckList;
		$this->collection->append('DisallowAttributeSets');
		$this->collection->append('DisallowCopy');
		$this->collection->append('DisallowDisableOutputEscaping');
		$this->collection->append('DisallowDynamicAttributeNames');
		$this->collection->append('DisallowDynamicElementNames');
		$this->collection->append('DisallowObjectParamsWithGeneratedName');
		$this->collection->append('DisallowPHPTags');
		$this->collection->append('DisallowUnsafeCopyOf');
		$this->collection->append('DisallowUnsafeDynamicCSS');
		$this->collection->append('DisallowUnsafeDynamicJS');
		$this->collection->append('DisallowUnsafeDynamicURL');
		$this->collection->append(new DisallowElementNS('http://icl.com/saxon', 'output'));
		$this->collection->append(new DisallowXPathFunction('document'));
		$this->collection->append(new RestrictFlashScriptAccess('sameDomain', \true));
	}
	public function checkTag(Tag $tag)
	{
		if (isset($tag->template) && !($tag->template instanceof UnsafeTemplate))
		{
			$template = (string) $tag->template;
			$this->checkTemplate($template, $tag);
		}
	}
	public function checkTemplate($template, Tag $tag = \null)
	{
		if ($this->disabled)
			return;
		if (!isset($tag))
			$tag = new Tag;
		$dom = TemplateHelper::loadTemplate($template);
		foreach ($this->collection as $check)
			$check->check($dom->documentElement, $tag);
	}
	public function disable()
	{
		$this->disabled = \true;
	}
	public function enable()
	{
		$this->disabled = \false;
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator;
use DOMElement;
abstract class TemplateNormalization
{
	const XMLNS_XSL = 'http://www.w3.org/1999/XSL/Transform';
	public $onlyOnce = \false;
	abstract public function normalize(DOMElement $template);
	public static function lowercase($str)
	{
		return \strtr($str, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz');
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license'); The MIT License
*/
namespace s9e\TextFormatter\Configurator;
use ArrayAccess;
use Iterator;
use s9e\TextFormatter\Configurator\Collections\TemplateNormalizationList;
use s9e\TextFormatter\Configurator\Helpers\TemplateHelper;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\Traits\CollectionProxy;
class TemplateNormalizer implements ArrayAccess, Iterator
{
	public function __call($methodName, $args)
	{
		return \call_user_func_array(array($this->collection, $methodName), $args);
	}
	public function offsetExists($offset)
	{
		return isset($this->collection[$offset]);
	}
	public function offsetGet($offset)
	{
		return $this->collection[$offset];
	}
	public function offsetSet($offset, $value)
	{
		$this->collection[$offset] = $value;
	}
	public function offsetUnset($offset)
	{
		unset($this->collection[$offset]);
	}
	public function count()
	{
		return \count($this->collection);
	}
	public function current()
	{
		return $this->collection->current();
	}
	public function key()
	{
		return $this->collection->key();
	}
	public function next()
	{
		return $this->collection->next();
	}
	public function rewind()
	{
		$this->collection->rewind();
	}
	public function valid()
	{
		return $this->collection->valid();
	}
	protected $collection;
	public function __construct()
	{
		$this->collection = new TemplateNormalizationList;
		$this->collection->append('PreserveSingleSpaces');
		$this->collection->append('RemoveComments');
		$this->collection->append('RemoveInterElementWhitespace');
		$this->collection->append('FixUnescapedCurlyBracesInHtmlAttributes');
		$this->collection->append('FoldArithmeticConstants');
		$this->collection->append('FoldConstantXPathExpressions');
		$this->collection->append('InlineAttributes');
		$this->collection->append('InlineCDATA');
		$this->collection->append('InlineElements');
		$this->collection->append('InlineInferredValues');
		$this->collection->append('InlineTextElements');
		$this->collection->append('InlineXPathLiterals');
		$this->collection->append('MinifyXPathExpressions');
		$this->collection->append('NormalizeAttributeNames');
		$this->collection->append('NormalizeElementNames');
		$this->collection->append('NormalizeUrls');
		$this->collection->append('OptimizeConditionalAttributes');
		$this->collection->append('OptimizeConditionalValueOf');
		$this->collection->append('OptimizeChoose');
		$this->collection->append('SetRelNoreferrerOnTargetedLinks');
	}
	public function normalizeTag(Tag $tag)
	{
		if (isset($tag->template) && !$tag->template->isNormalized())
			$tag->template->normalize($this);
	}
	public function normalizeTemplate($template)
	{
		$dom = TemplateHelper::loadTemplate($template);
		$applied = array();
		$loops = 5;
		do
		{
			$old = $template;
			foreach ($this->collection as $k => $normalization)
			{
				if (isset($applied[$k]) && !empty($normalization->onlyOnce))
					continue;
				$normalization->normalize($dom->documentElement);
				$applied[$k] = 1;
			}
			$template = TemplateHelper::saveTemplate($dom);
		}
		while (--$loops && $template !== $old);
		return $template;
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Validators;
use InvalidArgumentException;
abstract class AttributeName
{
	public static function isValid($name)
	{
		return (bool) \preg_match('#^(?!xmlns$)[a-z_][-a-z_0-9]*$#Di', $name);
	}
	public static function normalize($name)
	{
		if (!static::isValid($name))
			throw new InvalidArgumentException("Invalid attribute name '" . $name . "'");
		return \strtolower($name);
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Validators;
use InvalidArgumentException;
abstract class TagName
{
	public static function isValid($name)
	{
		return (bool) \preg_match('#^(?:(?!xmlns|xsl|s9e)[a-z_][a-z_0-9]*:)?[a-z_][-a-z_0-9]*$#Di', $name);
	}
	public static function normalize($name)
	{
		if (!static::isValid($name))
			throw new InvalidArgumentException("Invalid tag name '" . $name . "'");
		if (\strpos($name, ':') === \false)
			$name = \strtoupper($name);
		return $name;
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Collections;
use Countable;
use Iterator;
use s9e\TextFormatter\Configurator\ConfigProvider;
use s9e\TextFormatter\Configurator\Helpers\ConfigHelper;
class Collection implements ConfigProvider, Countable, Iterator
{
	protected $items = array();
	public function clear()
	{
		$this->items = array();
	}
	public function asConfig()
	{
		return ConfigHelper::toArray($this->items, \true);
	}
	public function count()
	{
		return \count($this->items);
	}
	public function current()
	{
		return \current($this->items);
	}
	public function key()
	{
		return \key($this->items);
	}
	public function next()
	{
		return \next($this->items);
	}
	public function rewind()
	{
		\reset($this->items);
	}
	public function valid()
	{
		return (\key($this->items) !== \null);
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Items;
use InvalidArgumentException;
use RuntimeException;
use Traversable;
use s9e\TextFormatter\Configurator\Collections\AttributeFilterChain;
use s9e\TextFormatter\Configurator\Collections\Collection;
use s9e\TextFormatter\Configurator\Collections\NormalizedCollection;
use s9e\TextFormatter\Configurator\ConfigProvider;
use s9e\TextFormatter\Configurator\Helpers\ConfigHelper;
use s9e\TextFormatter\Configurator\Items\ProgrammableCallback;
use s9e\TextFormatter\Configurator\Traits\Configurable;
use s9e\TextFormatter\Configurator\Traits\TemplateSafeness;
class Attribute implements ConfigProvider
{
	public function __get($propName)
	{
		$methodName = 'get' . \ucfirst($propName);
		if (\method_exists($this, $methodName))
			return $this->$methodName();
		if (!\property_exists($this, $propName))
			throw new RuntimeException("Property '" . $propName . "' does not exist");
		return $this->$propName;
	}
	public function __set($propName, $propValue)
	{
		$methodName = 'set' . \ucfirst($propName);
		if (\method_exists($this, $methodName))
		{
			$this->$methodName($propValue);
			return;
		}
		if (!isset($this->$propName))
		{
			$this->$propName = $propValue;
			return;
		}
		if ($this->$propName instanceof NormalizedCollection)
		{
			if (!\is_array($propValue)
			 && !($propValue instanceof Traversable))
				throw new InvalidArgumentException("Property '" . $propName . "' expects an array or a traversable object to be passed");
			$this->$propName->clear();
			foreach ($propValue as $k => $v)
				$this->$propName->set($k, $v);
			return;
		}
		if (\is_object($this->$propName))
		{
			if (!($propValue instanceof $this->$propName))
				throw new InvalidArgumentException("Cannot replace property '" . $propName . "' of class '" . \get_class($this->$propName) . "' with instance of '" . \get_class($propValue) . "'");
		}
		else
		{
			$oldType = \gettype($this->$propName);
			$newType = \gettype($propValue);
			if ($oldType === 'boolean')
				if ($propValue === 'false')
				{
					$newType   = 'boolean';
					$propValue = \false;
				}
				elseif ($propValue === 'true')
				{
					$newType   = 'boolean';
					$propValue = \true;
				}
			if ($oldType !== $newType)
			{
				$tmp = $propValue;
				\settype($tmp, $oldType);
				\settype($tmp, $newType);
				if ($tmp !== $propValue)
					throw new InvalidArgumentException("Cannot replace property '" . $propName . "' of type " . $oldType . ' with value of type ' . $newType);
				\settype($propValue, $oldType);
			}
		}
		$this->$propName = $propValue;
	}
	public function __isset($propName)
	{
		$methodName = 'isset' . \ucfirst($propName);
		if (\method_exists($this, $methodName))
			return $this->$methodName();
		return isset($this->$propName);
	}
	public function __unset($propName)
	{
		$methodName = 'unset' . \ucfirst($propName);
		if (\method_exists($this, $methodName))
		{
			$this->$methodName();
			return;
		}
		if (!isset($this->$propName))
			return;
		if ($this->$propName instanceof Collection)
		{
			$this->$propName->clear();
			return;
		}
		throw new RuntimeException("Property '" . $propName . "' cannot be unset");
	}
	protected $markedSafe = array();

	public function isSafeAsURL()
	{
		return $this->isSafe('AsURL');
	}
	public function isSafeInCSS()
	{
		return $this->isSafe('InCSS');
	}
	public function isSafeInJS()
	{
		return $this->isSafe('InJS');
	}
	public function markAsSafeAsURL()
	{
		$this->markedSafe['AsURL'] = \true;
		return $this;
	}
	public function markAsSafeInCSS()
	{
		$this->markedSafe['InCSS'] = \true;
		return $this;
	}
	public function markAsSafeInJS()
	{
		$this->markedSafe['InJS'] = \true;
		return $this;
	}
	public function resetSafeness()
	{
		$this->markedSafe = array();
		return $this;
	}
	protected $defaultValue;
	protected $filterChain;
	protected $generator;
	protected $required = \true;
	public function __construct(array $options = \null)
	{
		$this->filterChain = new AttributeFilterChain;
		if (isset($options))
			foreach ($options as $optionName => $optionValue)
				$this->__set($optionName, $optionValue);
	}
	protected function isSafe($context)
	{
		$methodName = 'isSafe' . $context;
		foreach ($this->filterChain as $filter)
			if ($filter->$methodName())
				return \true;
		return !empty($this->markedSafe[$context]);
	}
	public function setGenerator($callback)
	{
		if (!($callback instanceof ProgrammableCallback))
			$callback = new ProgrammableCallback($callback);
		$this->generator = $callback;
	}
	public function asConfig()
	{
		$vars = \get_object_vars($this);
		unset($vars['markedSafe']);
		return ConfigHelper::toArray($vars);
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Items;
use InvalidArgumentException;
use s9e\TextFormatter\Configurator\ConfigProvider;
use s9e\TextFormatter\Configurator\Helpers\ConfigHelper;
use s9e\TextFormatter\Configurator\JavaScript\Code;
use s9e\TextFormatter\Configurator\JavaScript\FunctionProvider;
class ProgrammableCallback implements ConfigProvider
{
	protected $callback;
	protected $js = 'returnFalse';
	protected $params = array();
	protected $vars = array();
	public function __construct($callback)
	{
		if (!\is_callable($callback))
			throw new InvalidArgumentException(__METHOD__ . '() expects a callback');
		$this->callback = $this->normalizeCallback($callback);
		$this->autoloadJS();
	}
	public function addParameterByValue($paramValue)
	{
		$this->params[] = $paramValue;
		return $this;
	}
	public function addParameterByName($paramName)
	{
		if (\array_key_exists($paramName, $this->params))
			throw new InvalidArgumentException("Parameter '" . $paramName . "' already exists");
		$this->params[$paramName] = \null;
		return $this;
	}
	public function getCallback()
	{
		return $this->callback;
	}
	public function getJS()
	{
		return $this->js;
	}
	public function getVars()
	{
		return $this->vars;
	}
	public function resetParameters()
	{
		$this->params = array();
		return $this;
	}
	public function setJS($js)
	{
		$this->js = $js;
		return $this;
	}
	public function setVar($name, $value)
	{
		$this->vars[$name] = $value;
		return $this;
	}
	public function setVars(array $vars)
	{
		$this->vars = $vars;
		return $this;
	}
	public function asConfig()
	{
		$config = array('callback' => $this->callback);
		foreach ($this->params as $k => $v)
			if (\is_numeric($k))
				$config['params'][] = $v;
			elseif (isset($this->vars[$k]))
				$config['params'][] = $this->vars[$k];
			else
				$config['params'][$k] = \null;
		if (isset($config['params']))
			$config['params'] = ConfigHelper::toArray($config['params'], \true, \true);
		$config['js'] = new Code($this->js);
		return $config;
	}
	protected function autoloadJS()
	{
		if (!\is_string($this->callback))
			return;
		try
		{
			$this->js = FunctionProvider::get($this->callback);
		}
		catch (InvalidArgumentException $e)
		{
			}
	}
	protected function normalizeCallback($callback)
	{
		if (\is_array($callback) && \is_string($callback[0]))
			$callback = $callback[0] . '::' . $callback[1];
		if (\is_string($callback))
			$callback = \ltrim($callback, '\\');
		return $callback;
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Items;
use InvalidArgumentException;
use s9e\TextFormatter\Configurator\ConfigProvider;
use s9e\TextFormatter\Configurator\FilterableConfigValue;
use s9e\TextFormatter\Configurator\Helpers\RegexpParser;
use s9e\TextFormatter\Configurator\JavaScript\Code;
use s9e\TextFormatter\Configurator\JavaScript\RegexpConvertor;
class Regexp implements ConfigProvider, FilterableConfigValue
{
	protected $isGlobal;
	protected $jsRegexp;
	protected $regexp;
	public function __construct($regexp, $isGlobal = \false)
	{
		if (@\preg_match($regexp, '') === \false)
			throw new InvalidArgumentException('Invalid regular expression ' . \var_export($regexp, \true));
		$this->regexp   = $regexp;
		$this->isGlobal = $isGlobal;
	}
	public function __toString()
	{
		return $this->regexp;
	}
	public function asConfig()
	{
		return $this;
	}
	public function filterConfig($target)
	{
		return ($target === 'JS') ? new Code($this->getJS()) : (string) $this;
	}
	public function getCaptureNames()
	{
		return RegexpParser::getCaptureNames($this->regexp);
	}
	public function getJS()
	{
		if (!isset($this->jsRegexp))
			$this->jsRegexp = RegexpConvertor::toJS($this->regexp, $this->isGlobal);
		return $this->jsRegexp;
	}
	public function getNamedCaptures()
	{
		$captures   = array();
		$regexpInfo = RegexpParser::parse($this->regexp);
		$start = $regexpInfo['delimiter'] . '^';
		$end   = '$' . $regexpInfo['delimiter'] . $regexpInfo['modifiers'];
		if (\strpos($regexpInfo['modifiers'], 'D') === \false)
			$end .= 'D';
		foreach ($this->getNamedCapturesExpressions($regexpInfo['tokens']) as $name => $expr)
			$captures[$name] = $start . $expr . $end;
		return $captures;
	}
	protected function getNamedCapturesExpressions(array $tokens)
	{
		$exprs = array();
		foreach ($tokens as $token)
		{
			if ($token['type'] !== 'capturingSubpatternStart' || !isset($token['name']))
				continue;
			$expr = $token['content'];
			if (\strpos($expr, '|') !== \false)
				$expr = '(?:' . $expr . ')';
			$exprs[$token['name']] = $expr;
		}
		return $exprs;
	}
	public function setJS($jsRegexp)
	{
		$this->jsRegexp = $jsRegexp;
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Items;
use InvalidArgumentException;
use RuntimeException;
use Traversable;
use s9e\TextFormatter\Configurator\Collections\AttributeCollection;
use s9e\TextFormatter\Configurator\Collections\AttributePreprocessorCollection;
use s9e\TextFormatter\Configurator\Collections\Collection;
use s9e\TextFormatter\Configurator\Collections\NormalizedCollection;
use s9e\TextFormatter\Configurator\Collections\Ruleset;
use s9e\TextFormatter\Configurator\Collections\TagFilterChain;
use s9e\TextFormatter\Configurator\ConfigProvider;
use s9e\TextFormatter\Configurator\Helpers\ConfigHelper;
use s9e\TextFormatter\Configurator\Items\Template;
use s9e\TextFormatter\Configurator\Traits\Configurable;
class Tag implements ConfigProvider
{
	public function __get($propName)
	{
		$methodName = 'get' . \ucfirst($propName);
		if (\method_exists($this, $methodName))
			return $this->$methodName();
		if (!\property_exists($this, $propName))
			throw new RuntimeException("Property '" . $propName . "' does not exist");
		return $this->$propName;
	}
	public function __set($propName, $propValue)
	{
		$methodName = 'set' . \ucfirst($propName);
		if (\method_exists($this, $methodName))
		{
			$this->$methodName($propValue);
			return;
		}
		if (!isset($this->$propName))
		{
			$this->$propName = $propValue;
			return;
		}
		if ($this->$propName instanceof NormalizedCollection)
		{
			if (!\is_array($propValue)
			 && !($propValue instanceof Traversable))
				throw new InvalidArgumentException("Property '" . $propName . "' expects an array or a traversable object to be passed");
			$this->$propName->clear();
			foreach ($propValue as $k => $v)
				$this->$propName->set($k, $v);
			return;
		}
		if (\is_object($this->$propName))
		{
			if (!($propValue instanceof $this->$propName))
				throw new InvalidArgumentException("Cannot replace property '" . $propName . "' of class '" . \get_class($this->$propName) . "' with instance of '" . \get_class($propValue) . "'");
		}
		else
		{
			$oldType = \gettype($this->$propName);
			$newType = \gettype($propValue);
			if ($oldType === 'boolean')
				if ($propValue === 'false')
				{
					$newType   = 'boolean';
					$propValue = \false;
				}
				elseif ($propValue === 'true')
				{
					$newType   = 'boolean';
					$propValue = \true;
				}
			if ($oldType !== $newType)
			{
				$tmp = $propValue;
				\settype($tmp, $oldType);
				\settype($tmp, $newType);
				if ($tmp !== $propValue)
					throw new InvalidArgumentException("Cannot replace property '" . $propName . "' of type " . $oldType . ' with value of type ' . $newType);
				\settype($propValue, $oldType);
			}
		}
		$this->$propName = $propValue;
	}
	public function __isset($propName)
	{
		$methodName = 'isset' . \ucfirst($propName);
		if (\method_exists($this, $methodName))
			return $this->$methodName();
		return isset($this->$propName);
	}
	public function __unset($propName)
	{
		$methodName = 'unset' . \ucfirst($propName);
		if (\method_exists($this, $methodName))
		{
			$this->$methodName();
			return;
		}
		if (!isset($this->$propName))
			return;
		if ($this->$propName instanceof Collection)
		{
			$this->$propName->clear();
			return;
		}
		throw new RuntimeException("Property '" . $propName . "' cannot be unset");
	}
	protected $attributes;
	protected $attributePreprocessors;
	protected $filterChain;
	protected $nestingLimit = 10;
	protected $rules;
	protected $tagLimit = 1000;
	protected $template;
	public function __construct(array $options = \null)
	{
		$this->attributes             = new AttributeCollection;
		$this->attributePreprocessors = new AttributePreprocessorCollection;
		$this->filterChain            = new TagFilterChain;
		$this->rules                  = new Ruleset;
		$this->filterChain->append('s9e\\TextFormatter\\Parser::executeAttributePreprocessors')
		                  ->addParameterByName('tagConfig')
		                  ->setJS('executeAttributePreprocessors');
		$this->filterChain->append('s9e\\TextFormatter\\Parser::filterAttributes')
		                  ->addParameterByName('tagConfig')
		                  ->addParameterByName('registeredVars')
		                  ->addParameterByName('logger')
		                  ->setJS('filterAttributes');
		if (isset($options))
		{
			\ksort($options);
			foreach ($options as $optionName => $optionValue)
				$this->__set($optionName, $optionValue);
		}
	}
	public function asConfig()
	{
		$vars = \get_object_vars($this);
		unset($vars['defaultChildRule']);
		unset($vars['defaultDescendantRule']);
		unset($vars['template']);
		if (!\count($this->attributePreprocessors))
		{
			$callback = 's9e\\TextFormatter\\Parser::executeAttributePreprocessors';
			$filterChain = clone $vars['filterChain'];
			$i = \count($filterChain);
			while (--$i >= 0)
				if ($filterChain[$i]->getCallback() === $callback)
					unset($filterChain[$i]);
			$vars['filterChain'] = $filterChain;
		}
		return ConfigHelper::toArray($vars);
	}
	public function getTemplate()
	{
		return $this->template;
	}
	public function issetTemplate()
	{
		return isset($this->template);
	}
	public function setAttributePreprocessors($attributePreprocessors)
	{
		$this->attributePreprocessors->clear();
		$this->attributePreprocessors->merge($attributePreprocessors);
	}
	public function setNestingLimit($limit)
	{
		$limit = (int) $limit;
		if ($limit < 1)
			throw new InvalidArgumentException('nestingLimit must be a number greater than 0');
		$this->nestingLimit = $limit;
	}
	public function setRules($rules)
	{
		$this->rules->clear();
		$this->rules->merge($rules);
	}
	public function setTagLimit($limit)
	{
		$limit = (int) $limit;
		if ($limit < 1)
			throw new InvalidArgumentException('tagLimit must be a number greater than 0');
		$this->tagLimit = $limit;
	}
	public function setTemplate($template)
	{
		if (!($template instanceof Template))
			$template = new Template($template);
		$this->template = $template;
	}
	public function unsetTemplate()
	{
		unset($this->template);
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\JavaScript;
use s9e\TextFormatter\Configurator\FilterableConfigValue;
class Code implements FilterableConfigValue
{
	public $code;
	public function __construct($code)
	{
		$this->code = $code;
	}
	public function __toString()
	{
		return (string) $this->code;
	}
	public function filterConfig($target)
	{
		return ($target === 'JS') ? $this : \null;
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RendererGenerators;
use DOMElement;
use s9e\TextFormatter\Configurator\Helpers\TemplateHelper;
use s9e\TextFormatter\Configurator\Helpers\TemplateParser;
use s9e\TextFormatter\Configurator\RendererGenerator;
use s9e\TextFormatter\Configurator\RendererGenerators\PHP\ControlStructuresOptimizer;
use s9e\TextFormatter\Configurator\RendererGenerators\PHP\Optimizer;
use s9e\TextFormatter\Configurator\RendererGenerators\PHP\Quick;
use s9e\TextFormatter\Configurator\RendererGenerators\PHP\Serializer;
use s9e\TextFormatter\Configurator\Rendering;
class PHP implements RendererGenerator
{
	const XMLNS_XSL = 'http://www.w3.org/1999/XSL/Transform';
	public $cacheDir;
	public $className;
	public $controlStructuresOptimizer;
	public $defaultClassPrefix = 'Renderer_';
	public $enableQuickRenderer = \true;
	public $filepath;
	public $lastClassName;
	public $lastFilepath;
	public $optimizer;
	public $serializer;
	public $useMultibyteStringFunctions;
	public function __construct($cacheDir = \null)
	{
		$this->cacheDir = (isset($cacheDir)) ? $cacheDir : \sys_get_temp_dir();
		if (\extension_loaded('tokenizer'))
		{
			$this->controlStructuresOptimizer = new ControlStructuresOptimizer;
			$this->optimizer = new Optimizer;
		}
		$this->useMultibyteStringFunctions = \extension_loaded('mbstring');
		$this->serializer = new Serializer;
	}
	public function getRenderer(Rendering $rendering)
	{
		$php = $this->generate($rendering);
		if (isset($this->filepath))
			$filepath = $this->filepath;
		else
			$filepath = $this->cacheDir . '/' . \str_replace('\\', '_', $this->lastClassName) . '.php';
		\file_put_contents($filepath, "<?php\n" . $php);
		$this->lastFilepath = \realpath($filepath);
		if (!\class_exists($this->lastClassName, \false))
			include $filepath;
		$renderer = new $this->lastClassName;
		$renderer->source = $php;
		return $renderer;
	}
	public function generate(Rendering $rendering)
	{
		$this->serializer->useMultibyteStringFunctions = $this->useMultibyteStringFunctions;
		$templates = $rendering->getTemplates();
		$groupedTemplates = array();
		foreach ($templates as $tagName => $template)
			$groupedTemplates[$template][] = $tagName;
		$hasApplyTemplatesSelect = \false;
		$tagBranch   = 0;
		$tagBranches = array();
		$compiledTemplates = array();
		$branchTables = array();
		foreach ($groupedTemplates as $template => $tagNames)
		{
			$ir = TemplateParser::parse($template);
			if (!$hasApplyTemplatesSelect)
				foreach ($ir->getElementsByTagName('applyTemplates') as $applyTemplates)
					if ($applyTemplates->hasAttribute('select'))
						$hasApplyTemplatesSelect = \true;
			$templateSource = $this->serializer->serialize($ir->documentElement);
			if (isset($this->optimizer))
				$templateSource = $this->optimizer->optimize($templateSource);
			$branchTables += $this->serializer->branchTables;
			$compiledTemplates[$tagBranch] = $templateSource;
			foreach ($tagNames as $tagName)
				$tagBranches[$tagName] = $tagBranch;
			++$tagBranch;
		}
		unset($groupedTemplates, $ir, $quickRender);
		$quickSource = \false;
		if ($this->enableQuickRenderer)
		{
			$quickRender = array();
			foreach ($tagBranches as $tagName => $tagBranch)
				$quickRender[$tagName] = $compiledTemplates[$tagBranch];
			$quickSource = Quick::getSource($quickRender);
			unset($quickRender);
		}
		$templatesSource = Quick::generateConditionals('$tb', $compiledTemplates);
		unset($compiledTemplates);
		if ($hasApplyTemplatesSelect)
			$needsXPath = \true;
		elseif (\strpos($templatesSource, '$this->getParamAsXPath') !== \false)
			$needsXPath = \true;
		elseif (\strpos($templatesSource, '$this->xpath') !== \false)
			$needsXPath = \true;
		else
			$needsXPath = \false;
		$php = array();
		$php[] = ' extends \\s9e\\TextFormatter\\Renderer';
		$php[] = '{';
		$php[] = '	protected $params=' . self::export($rendering->getAllParameters()) . ';';
		$php[] = '	protected static $tagBranches=' . self::export($tagBranches) . ';';
		foreach ($branchTables as $varName => $branchTable)
			$php[] = '	protected static $' . $varName . '=' . self::export($branchTable) . ';';
		if ($needsXPath)
			$php[] = '	protected $xpath;';
		$php[] = '	public function __sleep()';
		$php[] = '	{';
		$php[] = '		$props = get_object_vars($this);';
		$php[] = "		unset(\$props['out'], \$props['proc'], \$props['source']" . (($needsXPath) ? ", \$props['xpath']" : '') . ');';
		$php[] = '		return array_keys($props);';
		$php[] = '	}';
		$php[] = '	public function renderRichText($xml)';
		$php[] = '	{';
		if ($quickSource !== \false)
		{
			$php[] = '		if (!isset($this->quickRenderingTest) || !preg_match($this->quickRenderingTest, $xml))';
			$php[] = '		{';
			$php[] = '			try';
			$php[] = '			{';
			$php[] = '				return $this->renderQuick($xml);';
			$php[] = '			}';
			$php[] = '			catch (\\Exception $e)';
			$php[] = '			{';
			$php[] = '			}';
			$php[] = '		}';
		}
		$php[] = '		$dom = $this->loadXML($xml);';
		if ($needsXPath)
			$php[] = '		$this->xpath = new \\DOMXPath($dom);';
		$php[] = "		\$this->out = '';";
		$php[] = '		$this->at($dom->documentElement);';
		if ($needsXPath)
			$php[] = '		$this->xpath = null;';
		$php[] = '		return $this->out;';
		$php[] = '	}';
		if ($hasApplyTemplatesSelect)
			$php[] = '	protected function at(\\DOMNode $root, $xpath = null)';
		else
			$php[] = '	protected function at(\\DOMNode $root)';
		$php[] = '	{';
		$php[] = '		if ($root->nodeType === 3)';
		$php[] = '		{';
		$php[] = '			$this->out .= htmlspecialchars($root->textContent,' . \ENT_NOQUOTES . ');';
		$php[] = '		}';
		$php[] = '		else';
		$php[] = '		{';
		if ($hasApplyTemplatesSelect)
			$php[] = '			foreach (isset($xpath) ? $this->xpath->query($xpath, $root) : $root->childNodes as $node)';
		else
			$php[] = '			foreach ($root->childNodes as $node)';
		$php[] = '			{';
		$php[] = '				if (!isset(self::$tagBranches[$node->nodeName]))';
		$php[] = '				{';
		$php[] = '					$this->at($node);';
		$php[] = '				}';
		$php[] = '				else';
		$php[] = '				{';
		$php[] = '					$tb = self::$tagBranches[$node->nodeName];';
		$php[] = '					' . $templatesSource;
		$php[] = '				}';
		$php[] = '			}';
		$php[] = '		}';
		$php[] = '	}';
		if (\strpos($templatesSource, '$this->getParamAsXPath') !== \false)
		{
			$php[] = '	protected function getParamAsXPath($k)';
			$php[] = '	{';
			$php[] = '		if (!isset($this->params[$k]))';
			$php[] = '		{';
			$php[] = '			return "\'\'";';
			$php[] = '		}';
			$php[] = '		$str = $this->params[$k];';
			$php[] = '		if (strpos($str, "\'") === false)';
			$php[] = '		{';
			$php[] = '			return "\'$str\'";';
			$php[] = '		}';
			$php[] = '		if (strpos($str, \'"\') === false)';
			$php[] = '		{';
			$php[] = '			return "\\"$str\\"";';
			$php[] = '		}';
			$php[] = '		$toks = array();';
			$php[] = '		$c = \'"\';';
			$php[] = '		$pos = 0;';
			$php[] = '		while ($pos < strlen($str))';
			$php[] = '		{';
			$php[] = '			$spn = strcspn($str, $c, $pos);';
			$php[] = '			if ($spn)';
			$php[] = '			{';
			$php[] = '				$toks[] = $c . substr($str, $pos, $spn) . $c;';
			$php[] = '				$pos += $spn;';
			$php[] = '			}';
			$php[] = '			$c = ($c === \'"\') ? "\'" : \'"\';';
			$php[] = '		}';
			$php[] = '		return \'concat(\' . implode(\',\', $toks) . \')\';';
			$php[] = '	}';
		}
		if ($quickSource !== \false)
			$php[] = $quickSource;
		$php[] = '}';
		$php = \implode("\n", $php);
		if (isset($this->controlStructuresOptimizer))
			$php = $this->controlStructuresOptimizer->optimize($php);
		$className = (isset($this->className))
		           ? $this->className
		           : $this->defaultClassPrefix . \sha1($php);
		$this->lastClassName = $className;
		$header = "\n/**\n* @package   s9e\TextFormatter\n* @copyright Copyright (c) 2010-2016 The s9e Authors\n* @license   http://www.opensource.org/licenses/mit-license.php The MIT License\n*/\n";
		$pos = \strrpos($className, '\\');
		if ($pos !== \false)
		{
			$header .= 'namespace ' . \substr($className, 0, $pos) . ";\n\n";
			$className = \substr($className, 1 + $pos);
		}
		$php = $header . 'class ' . $className . $php;
		return $php;
	}
	protected static function export(array $value)
	{
		$pairs = array();
		foreach ($value as $k => $v)
			$pairs[] = \var_export($k, \true) . '=>' . \var_export($v, \true);
		return 'array(' . \implode(',', $pairs) . ')';
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RendererGenerators\PHP;
class ControlStructuresOptimizer extends AbstractOptimizer
{
	protected $braces;
	protected $context;
	protected function blockEndsWithIf()
	{
		return \in_array($this->context['lastBlock'], array(\T_IF, \T_ELSEIF), \true);
	}
	protected function isControlStructure()
	{
		return \in_array(
			$this->tokens[$this->i][0],
			array(\T_ELSE, \T_ELSEIF, \T_FOR, \T_FOREACH, \T_IF, \T_WHILE),
			\true
		);
	}
	protected function isFollowedByElse()
	{
		if ($this->i > $this->cnt - 4)
			return \false;
		$k = $this->i + 1;
		if ($this->tokens[$k][0] === \T_WHITESPACE)
			++$k;
		return \in_array($this->tokens[$k][0], array(\T_ELSEIF, \T_ELSE), \true);
	}
	protected function mustPreserveBraces()
	{
		return ($this->blockEndsWithIf() && $this->isFollowedByElse());
	}
	protected function optimizeTokens()
	{
		while (++$this->i < $this->cnt)
			if ($this->tokens[$this->i] === ';')
				++$this->context['statements'];
			elseif ($this->tokens[$this->i] === '{')
				++$this->braces;
			elseif ($this->tokens[$this->i] === '}')
			{
				if ($this->context['braces'] === $this->braces)
					$this->processEndOfBlock();
				--$this->braces;
			}
			elseif ($this->isControlStructure())
				$this->processControlStructure();
	}
	protected function processControlStructure()
	{
		$savedIndex = $this->i;
		if (!\in_array($this->tokens[$this->i][0], array(\T_ELSE, \T_ELSEIF), \true))
			++$this->context['statements'];
		if ($this->tokens[$this->i][0] !== \T_ELSE)
			$this->skipCondition();
		$this->skipWhitespace();
		if ($this->tokens[$this->i] !== '{')
		{
			$this->i = $savedIndex;
			return;
		}
		++$this->braces;
		$replacement = array(\T_WHITESPACE, '');
		if ($this->tokens[$savedIndex][0]  === \T_ELSE
		 && $this->tokens[$this->i + 1][0] !== \T_VARIABLE
		 && $this->tokens[$this->i + 1][0] !== \T_WHITESPACE)
			$replacement = array(\T_WHITESPACE, ' ');
		$this->context['lastBlock'] = $this->tokens[$savedIndex][0];
		$this->context = array(
			'braces'      => $this->braces,
			'index'       => $this->i,
			'lastBlock'   => \null,
			'parent'      => $this->context,
			'replacement' => $replacement,
			'savedIndex'  => $savedIndex,
			'statements'  => 0
		);
	}
	protected function processEndOfBlock()
	{
		if ($this->context['statements'] < 2 && !$this->mustPreserveBraces())
			$this->removeBracesInCurrentContext();
		$this->context = $this->context['parent'];
		$this->context['parent']['lastBlock'] = $this->context['lastBlock'];
	}
	protected function removeBracesInCurrentContext()
	{
		$this->tokens[$this->context['index']] = $this->context['replacement'];
		$this->tokens[$this->i] = ($this->context['statements']) ? array(\T_WHITESPACE, '') : ';';
		foreach (array($this->context['index'] - 1, $this->i - 1) as $tokenIndex)
			if ($this->tokens[$tokenIndex][0] === \T_WHITESPACE)
				$this->tokens[$tokenIndex][1] = '';
		if ($this->tokens[$this->context['savedIndex']][0] === \T_ELSE)
		{
			$j = 1 + $this->context['savedIndex'];
			while ($this->tokens[$j][0] === \T_WHITESPACE
			    || $this->tokens[$j][0] === \T_COMMENT
			    || $this->tokens[$j][0] === \T_DOC_COMMENT)
				++$j;
			if ($this->tokens[$j][0] === \T_IF)
			{
				$this->tokens[$j] = array(\T_ELSEIF, 'elseif');
				$j = $this->context['savedIndex'];
				$this->tokens[$j] = array(\T_WHITESPACE, '');
				if ($this->tokens[$j - 1][0] === \T_WHITESPACE)
					$this->tokens[$j - 1][1] = '';
				$this->unindentBlock($j, $this->i - 1);
				$this->tokens[$this->context['index']] = array(\T_WHITESPACE, '');
			}
		}
		$this->changed = \true;
	}
	protected function reset($php)
	{
		parent::reset($php);
		$this->braces  = 0;
		$this->context = array(
			'braces'      => 0,
			'index'       => -1,
			'parent'      => array(),
			'preventElse' => \false,
			'savedIndex'  => 0,
			'statements'  => 0
		);
	}
	protected function skipCondition()
	{
		$this->skipToString('(');
		$parens = 0;
		while (++$this->i < $this->cnt)
			if ($this->tokens[$this->i] === ')')
				if ($parens)
					--$parens;
				else
					break;
			elseif ($this->tokens[$this->i] === '(')
				++$parens;
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RulesGenerators;
use s9e\TextFormatter\Configurator\Helpers\TemplateForensics;
use s9e\TextFormatter\Configurator\RulesGenerators\Interfaces\BooleanRulesGenerator;
class AutoCloseIfVoid implements BooleanRulesGenerator
{
	public function generateBooleanRules(TemplateForensics $src)
	{
		return ($src->isVoid()) ? array('autoClose' => \true) : array();
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RulesGenerators;
use s9e\TextFormatter\Configurator\Helpers\TemplateForensics;
use s9e\TextFormatter\Configurator\RulesGenerators\Interfaces\BooleanRulesGenerator;
class AutoReopenFormattingElements implements BooleanRulesGenerator
{
	public function generateBooleanRules(TemplateForensics $src)
	{
		return ($src->isFormattingElement()) ? array('autoReopen' => \true) : array();
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RulesGenerators;
use s9e\TextFormatter\Configurator\Helpers\TemplateForensics;
use s9e\TextFormatter\Configurator\RulesGenerators\Interfaces\TargetedRulesGenerator;
class BlockElementsFosterFormattingElements implements TargetedRulesGenerator
{
	public function generateTargetedRules(TemplateForensics $src, TemplateForensics $trg)
	{
		return ($src->isBlock() && $src->isPassthrough() && $trg->isFormattingElement()) ? array('fosterParent') : array();
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RulesGenerators;
use s9e\TextFormatter\Configurator\Helpers\TemplateForensics;
use s9e\TextFormatter\Configurator\RulesGenerators\Interfaces\BooleanRulesGenerator;
class DisableAutoLineBreaksIfNewLinesArePreserved implements BooleanRulesGenerator
{
	public function generateBooleanRules(TemplateForensics $src)
	{
		return ($src->preservesNewLines()) ? array('disableAutoLineBreaks' => \true) : array();
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RulesGenerators;
use s9e\TextFormatter\Configurator\Helpers\TemplateForensics;
use s9e\TextFormatter\Configurator\RulesGenerators\Interfaces\BooleanRulesGenerator;
use s9e\TextFormatter\Configurator\RulesGenerators\Interfaces\TargetedRulesGenerator;
class EnforceContentModels implements BooleanRulesGenerator, TargetedRulesGenerator
{
	protected $br;
	protected $span;
	public function __construct()
	{
		$this->br   = new TemplateForensics('<br/>');
		$this->span = new TemplateForensics('<span><xsl:apply-templates/></span>');
	}
	public function generateBooleanRules(TemplateForensics $src)
	{
		$rules = array();
		if ($src->isTransparent())
			$rules['isTransparent'] = \true;
		if (!$src->allowsChild($this->br))
		{
			$rules['preventLineBreaks'] = \true;
			$rules['suspendAutoLineBreaks'] = \true;
		}
		if (!$src->allowsDescendant($this->br))
		{
			$rules['disableAutoLineBreaks'] = \true;
			$rules['preventLineBreaks'] = \true;
		}
		return $rules;
	}
	public function generateTargetedRules(TemplateForensics $src, TemplateForensics $trg)
	{
		if (!$src->allowsChildElements())
			$src = $this->span;
		$rules = array();
		if (!$src->allowsChild($trg))
			$rules[] = 'denyChild';
		if (!$src->allowsDescendant($trg))
			$rules[] = 'denyDescendant';
		return $rules;
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RulesGenerators;
use s9e\TextFormatter\Configurator\Helpers\TemplateForensics;
use s9e\TextFormatter\Configurator\RulesGenerators\Interfaces\TargetedRulesGenerator;
class EnforceOptionalEndTags implements TargetedRulesGenerator
{
	public function generateTargetedRules(TemplateForensics $src, TemplateForensics $trg)
	{
		return ($src->closesParent($trg)) ? array('closeParent') : array();
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RulesGenerators;
use DOMXPath;
use s9e\TextFormatter\Configurator\Helpers\TemplateForensics;
use s9e\TextFormatter\Configurator\RulesGenerators\Interfaces\BooleanRulesGenerator;
class IgnoreTagsInCode implements BooleanRulesGenerator
{
	public function generateBooleanRules(TemplateForensics $src)
	{
		$xpath = new DOMXPath($src->getDOM());
		if ($xpath->evaluate('count(//code//xsl:apply-templates)'))
			return array('ignoreTags' => \true);
		return array();
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RulesGenerators;
use s9e\TextFormatter\Configurator\Helpers\TemplateForensics;
use s9e\TextFormatter\Configurator\RulesGenerators\Interfaces\BooleanRulesGenerator;
class IgnoreTextIfDisallowed implements BooleanRulesGenerator
{
	public function generateBooleanRules(TemplateForensics $src)
	{
		return ($src->allowsText()) ? array() : array('ignoreText' => \true);
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RulesGenerators;
use s9e\TextFormatter\Configurator\Helpers\TemplateForensics;
use s9e\TextFormatter\Configurator\RulesGenerators\Interfaces\BooleanRulesGenerator;
class IgnoreWhitespaceAroundBlockElements implements BooleanRulesGenerator
{
	public function generateBooleanRules(TemplateForensics $src)
	{
		return ($src->isBlock()) ? array('ignoreSurroundingWhitespace' => \true) : array();
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RulesGenerators;
use DOMXPath;
use s9e\TextFormatter\Configurator\Helpers\TemplateForensics;
use s9e\TextFormatter\Configurator\RulesGenerators\Interfaces\BooleanRulesGenerator;
class TrimFirstLineInCodeBlocks implements BooleanRulesGenerator
{
	public function generateBooleanRules(TemplateForensics $src)
	{
		$rules = array();
		$xpath = new DOMXPath($src->getDOM());
		if ($xpath->evaluate('count(//pre//code//xsl:apply-templates)') > 0)
			$rules['trimFirstLine'] = \true;
		return $rules;
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateChecks;
use DOMAttr;
use DOMElement;
use DOMNode;
use DOMXPath;
use s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException;
use s9e\TextFormatter\Configurator\Helpers\AVTHelper;
use s9e\TextFormatter\Configurator\Items\Attribute;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\TemplateCheck;
abstract class AbstractDynamicContentCheck extends TemplateCheck
{
	protected $ignoreUnknownAttributes = \false;
	abstract protected function getNodes(DOMElement $template);
	abstract protected function isSafe(Attribute $attribute);
	public function check(DOMElement $template, Tag $tag)
	{
		foreach ($this->getNodes($template) as $node)
			$this->checkNode($node, $tag);
	}
	public function detectUnknownAttributes()
	{
		$this->ignoreUnknownAttributes = \false;
	}
	public function ignoreUnknownAttributes()
	{
		$this->ignoreUnknownAttributes = \true;
	}
	protected function checkAttribute(DOMNode $node, Tag $tag, $attrName)
	{
		if (!isset($tag->attributes[$attrName]))
		{
			if ($this->ignoreUnknownAttributes)
				return;
			throw new UnsafeTemplateException("Cannot assess the safety of unknown attribute '" . $attrName . "'", $node);
		}
		if (!$this->tagFiltersAttributes($tag) || !$this->isSafe($tag->attributes[$attrName]))
			throw new UnsafeTemplateException("Attribute '" . $attrName . "' is not properly sanitized to be used in this context", $node);
	}
	protected function checkAttributeNode(DOMAttr $attribute, Tag $tag)
	{
		foreach (AVTHelper::parse($attribute->value) as $token)
			if ($token[0] === 'expression')
				$this->checkExpression($attribute, $token[1], $tag);
	}
	protected function checkContext(DOMNode $node)
	{
		$xpath     = new DOMXPath($node->ownerDocument);
		$ancestors = $xpath->query('ancestor::xsl:for-each', $node);
		if ($ancestors->length)
			throw new UnsafeTemplateException("Cannot assess context due to '" . $ancestors->item(0)->nodeName . "'", $node);
	}
	protected function checkCopyOfNode(DOMElement $node, Tag $tag)
	{
		$this->checkSelectNode($node->getAttributeNode('select'), $tag);
	}
	protected function checkElementNode(DOMElement $element, Tag $tag)
	{
		$xpath = new DOMXPath($element->ownerDocument);
		$predicate = ($element->localName === 'attribute') ? '' : '[not(ancestor::xsl:attribute)]';
		$query = './/xsl:value-of' . $predicate;
		foreach ($xpath->query($query, $element) as $valueOf)
			$this->checkSelectNode($valueOf->getAttributeNode('select'), $tag);
		$query = './/xsl:apply-templates' . $predicate;
		foreach ($xpath->query($query, $element) as $applyTemplates)
			throw new UnsafeTemplateException('Cannot allow unfiltered data in this context', $applyTemplates);
	}
	protected function checkExpression(DOMNode $node, $expr, Tag $tag)
	{
		$this->checkContext($node);
		if (\preg_match('/^\\$(\\w+)$/', $expr, $m))
		{
			$this->checkVariable($node, $tag, $m[1]);
			return;
		}
		if ($this->isExpressionSafe($expr))
			return;
		if (\preg_match('/^@(\\w+)$/', $expr, $m))
		{
			$this->checkAttribute($node, $tag, $m[1]);
			return;
		}
		throw new UnsafeTemplateException("Cannot assess the safety of expression '" . $expr . "'", $node);
	}
	protected function checkNode(DOMNode $node, Tag $tag)
	{
		if ($node instanceof DOMAttr)
			$this->checkAttributeNode($node, $tag);
		elseif ($node instanceof DOMElement)
			if ($node->namespaceURI === self::XMLNS_XSL
			 && $node->localName    === 'copy-of')
				$this->checkCopyOfNode($node, $tag);
			else
				$this->checkElementNode($node, $tag);
	}
	protected function checkVariable(DOMNode $node, $tag, $qname)
	{
		$this->checkVariableDeclaration($node, $tag, 'xsl:param[@name="' . $qname . '"]');
		$this->checkVariableDeclaration($node, $tag, 'xsl:variable[@name="' . $qname . '"]');
	}
	protected function checkVariableDeclaration(DOMNode $node, $tag, $query)
	{
		$query = 'ancestor-or-self::*/preceding-sibling::' . $query . '[@select]';
		$xpath = new DOMXPath($node->ownerDocument);
		foreach ($xpath->query($query, $node) as $varNode)
		{
			try
			{
				$this->checkExpression($varNode, $varNode->getAttribute('select'), $tag);
			}
			catch (UnsafeTemplateException $e)
			{
				$e->setNode($node);
				throw $e;
			}
		}
	}
	protected function checkSelectNode(DOMAttr $select, Tag $tag)
	{
		$this->checkExpression($select, $select->value, $tag);
	}
	protected function isExpressionSafe($expr)
	{
		return \false;
	}
	protected function tagFiltersAttributes(Tag $tag)
	{
		return $tag->filterChain->containsCallback('s9e\\TextFormatter\\Parser::filterAttributes');
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateChecks;
use DOMElement;
use DOMNode;
use DOMXPath;
use s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\TemplateCheck;
abstract class AbstractFlashRestriction extends TemplateCheck
{
	public $defaultSetting;
	public $maxSetting;
	public $onlyIfDynamic;
	protected $settingName;
	protected $settings;
	protected $template;
	public function __construct($maxSetting, $onlyIfDynamic = \false)
	{
		$this->maxSetting    = $maxSetting;
		$this->onlyIfDynamic = $onlyIfDynamic;
	}
	public function check(DOMElement $template, Tag $tag)
	{
		$this->template = $template;
		$this->checkEmbeds();
		$this->checkObjects();
	}
	protected function checkAttributes(DOMElement $embed)
	{
		$settingName = \strtolower($this->settingName);
		$useDefault  = \true;
		foreach ($embed->attributes as $attribute)
		{
			$attrName = \strtolower($attribute->name);
			if ($attrName === $settingName)
			{
				$this->checkSetting($attribute, $attribute->value);
				$useDefault = \false;
			}
		}
		if ($useDefault)
			$this->checkSetting($embed, $this->defaultSetting);
	}
	protected function checkDynamicAttributes(DOMElement $embed)
	{
		$settingName = \strtolower($this->settingName);
		foreach ($embed->getElementsByTagNameNS(self::XMLNS_XSL, 'attribute') as $attribute)
		{
			$attrName = \strtolower($attribute->getAttribute('name'));
			if ($attrName === $settingName)
				throw new UnsafeTemplateException('Cannot assess the safety of dynamic attributes', $attribute);
		}
	}
	protected function checkDynamicParams(DOMElement $object)
	{
		foreach ($this->getObjectParams($object) as $param)
			foreach ($param->getElementsByTagNameNS(self::XMLNS_XSL, 'attribute') as $attribute)
				if (\strtolower($attribute->getAttribute('name')) === 'value')
					throw new UnsafeTemplateException('Cannot assess the safety of dynamic attributes', $attribute);
	}
	protected function checkEmbeds()
	{
		foreach ($this->getElements('embed') as $embed)
		{
			$this->checkDynamicAttributes($embed);
			$this->checkAttributes($embed);
		}
	}
	protected function checkObjects()
	{
		foreach ($this->getElements('object') as $object)
		{
			$this->checkDynamicParams($object);
			$params = $this->getObjectParams($object);
			foreach ($params as $param)
				$this->checkSetting($param, $param->getAttribute('value'));
			if (empty($params))
				$this->checkSetting($object, $this->defaultSetting);
		}
	}
	protected function checkSetting(DOMNode $node, $setting)
	{
		if (!isset($this->settings[\strtolower($setting)]))
		{
			if (\preg_match('/(?<!\\{)\\{(?:\\{\\{)*(?!\\{)/', $setting))
				throw new UnsafeTemplateException('Cannot assess ' . $this->settingName . " setting '" . $setting . "'", $node);
			throw new UnsafeTemplateException('Unknown ' . $this->settingName . " value '" . $setting . "'", $node);
		}
		$value    = $this->settings[\strtolower($setting)];
		$maxValue = $this->settings[\strtolower($this->maxSetting)];
		if ($value > $maxValue)
			throw new UnsafeTemplateException($this->settingName . " setting '" . $setting . "' exceeds restricted value '" . $this->maxSetting . "'", $node);
	}
	protected function isDynamic(DOMElement $node)
	{
		if ($node->getElementsByTagNameNS(self::XMLNS_XSL, '*')->length)
			return \true;
		$xpath = new DOMXPath($node->ownerDocument);
		$query = './/@*[contains(., "{")]';
		foreach ($xpath->query($query, $node) as $attribute)
			if (\preg_match('/(?<!\\{)\\{(?:\\{\\{)*(?!\\{)/', $attribute->value))
				return \true;
		return \false;
	}
	protected function getElements($tagName)
	{
		$nodes = array();
		foreach ($this->template->ownerDocument->getElementsByTagName($tagName) as $node)
			if (!$this->onlyIfDynamic || $this->isDynamic($node))
				$nodes[] = $node;
		return $nodes;
	}
	protected function getObjectParams(DOMElement $object)
	{
		$params      = array();
		$settingName = \strtolower($this->settingName);
		foreach ($object->getElementsByTagName('param') as $param)
		{
			$paramName = \strtolower($param->getAttribute('name'));
			if ($paramName === $settingName && $param->parentNode->isSameNode($object))
				$params[] = $param;
		}
		return $params;
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateChecks;
use DOMElement;
use DOMXPath;
use s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\TemplateCheck;
class DisallowAttributeSets extends TemplateCheck
{
	public function check(DOMElement $template, Tag $tag)
	{
		$xpath = new DOMXPath($template->ownerDocument);
		$nodes = $xpath->query('//@use-attribute-sets');
		if ($nodes->length)
			throw new UnsafeTemplateException('Cannot assess the safety of attribute sets', $nodes->item(0));
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateChecks;
use DOMElement;
use s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\TemplateCheck;
class DisallowCopy extends TemplateCheck
{
	public function check(DOMElement $template, Tag $tag)
	{
		$nodes = $template->getElementsByTagNameNS(self::XMLNS_XSL, 'copy');
		$node  = $nodes->item(0);
		if ($node)
			throw new UnsafeTemplateException("Cannot assess the safety of an '" . $node->nodeName . "' element", $node);
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateChecks;
use DOMElement;
use DOMXPath;
use s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\TemplateCheck;
class DisallowDisableOutputEscaping extends TemplateCheck
{
	public function check(DOMElement $template, Tag $tag)
	{
		$xpath = new DOMXPath($template->ownerDocument);
		$node  = $xpath->query('//@disable-output-escaping')->item(0);
		if ($node)
			throw new UnsafeTemplateException("The template contains a 'disable-output-escaping' attribute", $node);
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateChecks;
use DOMElement;
use s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\TemplateCheck;
class DisallowDynamicAttributeNames extends TemplateCheck
{
	public function check(DOMElement $template, Tag $tag)
	{
		$nodes = $template->getElementsByTagNameNS(self::XMLNS_XSL, 'attribute');
		foreach ($nodes as $node)
			if (\strpos($node->getAttribute('name'), '{') !== \false)
				throw new UnsafeTemplateException('Dynamic <xsl:attribute/> names are disallowed', $node);
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateChecks;
use DOMElement;
use s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\TemplateCheck;
class DisallowDynamicElementNames extends TemplateCheck
{
	public function check(DOMElement $template, Tag $tag)
	{
		$nodes = $template->getElementsByTagNameNS(self::XMLNS_XSL, 'element');
		foreach ($nodes as $node)
			if (\strpos($node->getAttribute('name'), '{') !== \false)
				throw new UnsafeTemplateException('Dynamic <xsl:element/> names are disallowed', $node);
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateChecks;
use DOMElement;
use s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\TemplateCheck;
class DisallowElementNS extends TemplateCheck
{
	public $elName;
	public $namespaceURI;
	public function __construct($namespaceURI, $elName)
	{
		$this->namespaceURI  = $namespaceURI;
		$this->elName        = $elName;
	}
	public function check(DOMElement $template, Tag $tag)
	{
		$node = $template->getElementsByTagNameNS($this->namespaceURI, $this->elName)->item(0);
		if ($node)
			throw new UnsafeTemplateException("Element '" . $node->nodeName . "' is disallowed", $node);
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateChecks;
use DOMElement;
use DOMXPath;
use s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\TemplateCheck;
class DisallowObjectParamsWithGeneratedName extends TemplateCheck
{
	public function check(DOMElement $template, Tag $tag)
	{
		$xpath = new DOMXPath($template->ownerDocument);
		$query = '//object//param[contains(@name, "{") or .//xsl:attribute[translate(@name, "NAME", "name") = "name"]]';
		$nodes = $xpath->query($query);
		foreach ($nodes as $node)
			throw new UnsafeTemplateException("A 'param' element with a suspect name has been found", $node);
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateChecks;
use DOMElement;
use DOMXPath;
use s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\TemplateCheck;
class DisallowPHPTags extends TemplateCheck
{
	public function check(DOMElement $template, Tag $tag)
	{
		$queries = array(
			'//processing-instruction()["php" = translate(name(),"HP","hp")]'
				=> 'PHP tags are not allowed in the template',
			'//script["php" = translate(@language,"HP","hp")]'
				=> 'PHP tags are not allowed in the template',
			'//xsl:processing-instruction["php" = translate(@name,"HP","hp")]'
				=> 'PHP tags are not allowed in the output',
			'//xsl:processing-instruction[contains(@name, "{")]'
				=> 'Dynamic processing instructions are not allowed',
		);
		$xpath = new DOMXPath($template->ownerDocument);
		foreach ($queries as $query => $error)
		{
			$nodes = $xpath->query($query); 
			if ($nodes->length)
				throw new UnsafeTemplateException($error, $nodes->item(0));
		}
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateChecks;
use DOMElement;
use s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\TemplateCheck;
class DisallowUnsafeCopyOf extends TemplateCheck
{
	public function check(DOMElement $template, Tag $tag)
	{
		$nodes = $template->getElementsByTagNameNS(self::XMLNS_XSL, 'copy-of');
		foreach ($nodes as $node)
		{
			$expr = $node->getAttribute('select');
			if (!\preg_match('#^@[-\\w]*$#D', $expr))
				throw new UnsafeTemplateException("Cannot assess the safety of '" . $node->nodeName . "' select expression '" . $expr . "'", $node);
		}
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateChecks;
use DOMElement;
use DOMXPath;
use s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException;
use s9e\TextFormatter\Configurator\Helpers\AVTHelper;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\TemplateCheck;
class DisallowXPathFunction extends TemplateCheck
{
	public $funcName;
	public function __construct($funcName)
	{
		$this->funcName = $funcName;
	}
	public function check(DOMElement $template, Tag $tag)
	{
		$regexp = '#(?!<\\pL)' . \preg_quote($this->funcName, '#') . '\\s*\\(#iu';
		$regexp = \str_replace('\\:', '\\s*:\\s*', $regexp);
		foreach ($this->getExpressions($template) as $expr => $node)
		{
			$expr = \preg_replace('#([\'"]).*?\\1#s', '', $expr);
			if (\preg_match($regexp, $expr))
				throw new UnsafeTemplateException('An XPath expression uses the ' . $this->funcName . '() function', $node);
		}
	}
	protected function getExpressions(DOMElement $template)
	{
		$xpath = new DOMXPath($template->ownerDocument);
		$exprs = array();
		foreach ($xpath->query('//@*') as $attribute)
			if ($attribute->parentNode->namespaceURI === self::XMLNS_XSL)
			{
				$expr = $attribute->value;
				$exprs[$expr] = $attribute;
			}
			else
				foreach (AVTHelper::parse($attribute->value) as $token)
					if ($token[0] === 'expression')
						$exprs[$token[1]] = $attribute;
		return $exprs;
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;
use DOMAttr;
use DOMElement;
use DOMXPath;
use s9e\TextFormatter\Configurator\Helpers\AVTHelper;
use s9e\TextFormatter\Configurator\TemplateNormalization;
abstract class AbstractConstantFolding extends TemplateNormalization
{
	abstract protected function getOptimizationPasses();
	public function normalize(DOMElement $template)
	{
		$xpath = new DOMXPath($template->ownerDocument);
		$query = '//*[namespace-uri() != "' . self::XMLNS_XSL . '"]/@*[contains(.,"{")]';
		foreach ($xpath->query($query) as $attribute)
			$this->replaceAVT($attribute);
		foreach ($template->getElementsByTagNameNS(self::XMLNS_XSL, 'value-of') as $valueOf)
			$this->replaceValueOf($valueOf);
	}
	public function evaluateExpression($expr)
	{
		$original = $expr;
		foreach ($this->getOptimizationPasses() as $regexp => $methodName)
		{
			$regexp = \str_replace(' ', '\\s*', $regexp);
			$expr   = \preg_replace_callback($regexp, array($this, $methodName), $expr);
		}
		return ($expr === $original) ? $expr : $this->evaluateExpression(\trim($expr));
	}
	protected function replaceAVT(DOMAttr $attribute)
	{
		$_this = $this;
		AVTHelper::replace(
			$attribute,
			function ($token) use ($_this)
			{
				if ($token[0] === 'expression')
					$token[1] = $_this->evaluateExpression($token[1]);
				return $token;
			}
		);
	}
	protected function replaceValueOf(DOMElement $valueOf)
	{
		$valueOf->setAttribute('select', $this->evaluateExpression($valueOf->getAttribute('select')));
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;
use DOMAttr;
use DOMElement;
use DOMXPath;
use s9e\TextFormatter\Configurator\TemplateNormalization;
class FixUnescapedCurlyBracesInHtmlAttributes extends TemplateNormalization
{
	public function normalize(DOMElement $template)
	{
		$dom   = $template->ownerDocument;
		$xpath = new DOMXPath($dom);
		$query = '//@*[contains(., "{")]';
		foreach ($xpath->query($query) as $attribute)
			$this->fixAttribute($attribute);
	}
	protected function fixAttribute(DOMAttr $attribute)
	{
		$parentNode = $attribute->parentNode;
		if ($parentNode->namespaceURI === self::XMLNS_XSL)
			return;
		$attribute->value = \htmlspecialchars(
			\preg_replace(
				'(\\b(?:do|else|(?:if|while)\\s*\\(.*?\\))\\s*\\{(?![{@]))',
				'$0{',
				$attribute->value
			),
			\ENT_NOQUOTES,
			'UTF-8'
		);
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;
use DOMElement;
use DOMException;
use DOMText;
use DOMXPath;
use s9e\TextFormatter\Configurator\TemplateNormalization;
class InlineAttributes extends TemplateNormalization
{
	public function normalize(DOMElement $template)
	{
		$xpath = new DOMXPath($template->ownerDocument);
		$query = '//*[namespace-uri() != "' . self::XMLNS_XSL . '"]/xsl:attribute';
		foreach ($xpath->query($query) as $attribute)
			$this->inlineAttribute($attribute);
	}
	protected function inlineAttribute(DOMElement $attribute)
	{
		$value = '';
		foreach ($attribute->childNodes as $node)
			if ($node instanceof DOMText
			 || array($node->namespaceURI, $node->localName) === array(self::XMLNS_XSL, 'text'))
				$value .= \preg_replace('([{}])', '$0$0', $node->textContent);
			elseif (array($node->namespaceURI, $node->localName) === array(self::XMLNS_XSL, 'value-of'))
				$value .= '{' . $node->getAttribute('select') . '}';
			else
				return;
		$attribute->parentNode->setAttribute($attribute->getAttribute('name'), $value);
		$attribute->parentNode->removeChild($attribute);
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;
use DOMElement;
use DOMXPath;
use s9e\TextFormatter\Configurator\TemplateNormalization;
class InlineCDATA extends TemplateNormalization
{
	public function normalize(DOMElement $template)
	{
		$dom   = $template->ownerDocument;
		$xpath = new DOMXPath($dom);
		foreach ($xpath->query('//text()') as $textNode)
			if ($textNode->nodeType === \XML_CDATA_SECTION_NODE)
				$textNode->parentNode->replaceChild(
					$dom->createTextNode($textNode->textContent),
					$textNode
				);
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;
use DOMElement;
use DOMException;
use s9e\TextFormatter\Configurator\TemplateNormalization;
class InlineElements extends TemplateNormalization
{
	public function normalize(DOMElement $template)
	{
		$dom = $template->ownerDocument;
		foreach ($template->getElementsByTagNameNS(self::XMLNS_XSL, 'element') as $element)
		{
			$elName = $element->getAttribute('name');
			try
			{
				$newElement = ($element->hasAttribute('namespace'))
				            ? $dom->createElementNS($element->getAttribute('namespace'), $elName)
				            : $dom->createElement($elName);
			}
			catch (DOMException $e)
			{
				continue;
			}
			$element->parentNode->replaceChild($newElement, $element);
			while ($element->firstChild)
				$newElement->appendChild($element->removeChild($element->firstChild));
		}
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;
use DOMAttr;
use DOMElement;
use DOMNode;
use DOMXPath;
use s9e\TextFormatter\Configurator\Helpers\AVTHelper;
use s9e\TextFormatter\Configurator\Helpers\TemplateParser;
use s9e\TextFormatter\Configurator\TemplateNormalization;
class InlineInferredValues extends TemplateNormalization
{
	public function normalize(DOMElement $template)
	{
		$xpath = new DOMXPath($template->ownerDocument);
		$query = '//xsl:if | //xsl:when';
		foreach ($xpath->query($query) as $node)
		{
			$map = TemplateParser::parseEqualityExpr($node->getAttribute('test'));
			if ($map === \false || \count($map) !== 1 || \count($map[\key($map)]) !== 1)
				continue;
			$expr  = \key($map);
			$value = \end($map[$expr]);
			$this->inlineInferredValue($node, $expr, $value);
		}
	}
	protected function inlineInferredValue(DOMNode $node, $expr, $value)
	{
		$xpath = new DOMXPath($node->ownerDocument);
		$query = './/xsl:value-of[@select="' . $expr . '"]';
		foreach ($xpath->query($query, $node) as $valueOf)
			$this->replaceValueOf($valueOf, $value);
		$query = './/*[namespace-uri() != "' . self::XMLNS_XSL . '"]/@*[contains(., "{' . $expr . '}")]';
		foreach ($xpath->query($query, $node) as $attribute)
			$this->replaceAttribute($attribute, $expr, $value);
	}
	protected function replaceAttribute(DOMAttr $attribute, $expr, $value)
	{
		AVTHelper::replace(
			$attribute,
			function ($token) use ($expr, $value)
			{
				if ($token[0] === 'expression' && $token[1] === $expr)
					$token = array('literal', $value);
				return $token;
			}
		);
	}
	protected function replaceValueOf(DOMElement $valueOf, $value)
	{
		$valueOf->parentNode->replaceChild(
			$valueOf->ownerDocument->createTextNode($value),
			$valueOf
		);
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;
use DOMElement;
use DOMXPath;
use s9e\TextFormatter\Configurator\TemplateNormalization;
class InlineTextElements extends TemplateNormalization
{
	public function normalize(DOMElement $template)
	{
		$dom   = $template->ownerDocument;
		$xpath = new DOMXPath($dom);
		foreach ($xpath->query('//xsl:text') as $node)
		{
			if (\trim($node->textContent) === '')
				if ($node->previousSibling && $node->previousSibling->nodeType === \XML_TEXT_NODE)
					;
				elseif ($node->nextSibling && $node->nextSibling->nodeType === \XML_TEXT_NODE)
					;
				else
					continue;
			$node->parentNode->replaceChild(
				$dom->createTextNode($node->textContent),
				$node
			);
		}
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;
use DOMElement;
use DOMXPath;
use s9e\TextFormatter\Configurator\TemplateNormalization;
use s9e\TextFormatter\Configurator\Helpers\AVTHelper;
class InlineXPathLiterals extends TemplateNormalization
{
	public function normalize(DOMElement $template)
	{
		$_this = $this;
		$xpath = new DOMXPath($template->ownerDocument);
		foreach ($xpath->query('//xsl:value-of') as $valueOf)
		{
			$textContent = $this->getTextContent($valueOf->getAttribute('select'));
			if ($textContent !== \false)
				$this->replaceElement($valueOf, $textContent);
		}
		$query = '//*[namespace-uri() != "' . self::XMLNS_XSL . '"]/@*[contains(., "{")]';
		foreach ($xpath->query($query) as $attribute)
		{
			AVTHelper::replace(
				$attribute,
				function ($token) use ($_this)
				{
					if ($token[0] === 'expression')
					{
						$textContent = $_this->getTextContent($token[1]);
						if ($textContent !== \false)
							$token = array('literal', $textContent);
					}
					return $token;
				}
			);
		}
	}
	public function getTextContent($expr)
	{
		$expr = \trim($expr);
		if (\preg_match('(^(?:\'[^\']*\'|"[^"]*")$)', $expr))
			return \substr($expr, 1, -1);
		if (\preg_match('(^0*([0-9]+(?:\\.[0-9]+)?)$)', $expr, $m))
			return $m[1];
		return \false;
	}
	protected function replaceElement(DOMElement $valueOf, $textContent)
	{
		$valueOf->parentNode->replaceChild(
			$valueOf->ownerDocument->createTextNode($textContent),
			$valueOf
		);
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;
use DOMElement;
use DOMXPath;
use s9e\TextFormatter\Configurator\Helpers\AVTHelper;
use s9e\TextFormatter\Configurator\Helpers\XPathHelper;
use s9e\TextFormatter\Configurator\TemplateNormalization;
class MinifyXPathExpressions extends TemplateNormalization
{
	public function normalize(DOMElement $template)
	{
		$xpath = new DOMXPath($template->ownerDocument);
		$query = '//xsl:*/@*[contains(., " ")][contains("matchselectest", name())]';
		foreach ($xpath->query($query) as $attribute)
			$attribute->parentNode->setAttribute(
				$attribute->nodeName,
				XPathHelper::minify($attribute->nodeValue)
			);
		$query = '//*[namespace-uri() != "' . self::XMLNS_XSL . '"]/@*[contains(., " ")]';
		foreach ($xpath->query($query) as $attribute)
		{
			AVTHelper::replace(
				$attribute,
				function ($token)
				{
					if ($token[0] === 'expression')
						$token[1] = XPathHelper::minify($token[1]);
					return $token;
				}
			);
		}
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;
use DOMElement;
use DOMXPath;
use s9e\TextFormatter\Configurator\TemplateNormalization;
class NormalizeAttributeNames extends TemplateNormalization
{
	public function normalize(DOMElement $template)
	{
		$xpath = new DOMXPath($template->ownerDocument);
		foreach ($xpath->query('.//@*', $template) as $attribute)
		{
			$attrName = self::lowercase($attribute->localName);
			if ($attrName !== $attribute->localName)
			{
				$attribute->parentNode->setAttribute($attrName, $attribute->value);
				$attribute->parentNode->removeAttributeNode($attribute);
			}
		}
		foreach ($xpath->query('//xsl:attribute[not(contains(@name, "{"))]') as $attribute)
		{
			$attrName = self::lowercase($attribute->getAttribute('name'));
			$attribute->setAttribute('name', $attrName);
		}
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;
use DOMElement;
use DOMXPath;
use s9e\TextFormatter\Configurator\TemplateNormalization;
class NormalizeElementNames extends TemplateNormalization
{
	public function normalize(DOMElement $template)
	{
		$dom   = $template->ownerDocument;
		$xpath = new DOMXPath($dom);
		foreach ($xpath->query('//*[namespace-uri() != "' . self::XMLNS_XSL . '"]') as $element)
		{
			$elName = self::lowercase($element->localName);
			if ($elName === $element->localName)
				continue;
			$newElement = (\is_null($element->namespaceURI))
			            ? $dom->createElement($elName)
			            : $dom->createElementNS($element->namespaceURI, $elName);
			while ($element->firstChild)
				$newElement->appendChild($element->removeChild($element->firstChild));
			foreach ($element->attributes as $attribute)
				$newElement->setAttributeNS(
					$attribute->namespaceURI,
					$attribute->nodeName,
					$attribute->value
				);
			$element->parentNode->replaceChild($newElement, $element);
		}
		foreach ($xpath->query('//xsl:element[not(contains(@name, "{"))]') as $element)
		{
			$elName = self::lowercase($element->getAttribute('name'));
			$element->setAttribute('name', $elName);
		}
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;
use DOMAttr;
use DOMElement;
use DOMXPath;
use s9e\TextFormatter\Configurator\Helpers\AVTHelper;
use s9e\TextFormatter\Configurator\Helpers\TemplateHelper;
use s9e\TextFormatter\Configurator\TemplateNormalization;
use s9e\TextFormatter\Parser\BuiltInFilters;
class NormalizeUrls extends TemplateNormalization
{
	public function normalize(DOMElement $template)
	{
		foreach (TemplateHelper::getURLNodes($template->ownerDocument) as $node)
			if ($node instanceof DOMAttr)
				$this->normalizeAttribute($node);
			elseif ($node instanceof DOMElement)
				$this->normalizeElement($node);
	}
	protected function normalizeAttribute(DOMAttr $attribute)
	{
		$tokens = AVTHelper::parse(\trim($attribute->value));
		$attrValue = '';
		foreach ($tokens as $_f6b3b659)
		{
			list($type, $content) = $_f6b3b659;
			if ($type === 'literal')
				$attrValue .= BuiltInFilters::sanitizeUrl($content);
			else
				$attrValue .= '{' . $content . '}';
		}
		$attrValue = $this->unescapeBrackets($attrValue);
		$attribute->value = \htmlspecialchars($attrValue);
	}
	protected function normalizeElement(DOMElement $element)
	{
		$xpath = new DOMXPath($element->ownerDocument);
		$query = './/text()[normalize-space() != ""]';
		foreach ($xpath->query($query, $element) as $i => $node)
		{
			$value = BuiltInFilters::sanitizeUrl($node->nodeValue);
			if (!$i)
				$value = $this->unescapeBrackets(\ltrim($value));
			$node->nodeValue = $value;
		}
		if (isset($node))
			$node->nodeValue = \rtrim($node->nodeValue);
	}
	protected function unescapeBrackets($url)
	{
		return \preg_replace('#^(\\w+://)%5B([-\\w:._%]+)%5D#i', '$1[$2]', $url);
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;
use DOMElement;
use DOMNode;
use DOMXPath;
use s9e\TextFormatter\Configurator\TemplateNormalization;
class OptimizeChoose extends TemplateNormalization
{
	protected $choose;
	protected $xpath;
	public function normalize(DOMElement $template)
	{
		$this->xpath = new DOMXPath($template->ownerDocument);
		foreach ($template->getElementsByTagNameNS(self::XMLNS_XSL, 'choose') as $choose)
		{
			$this->choose = $choose;
			$this->optimizeChooseElement();
		}
	}
	protected function adoptChildren(DOMElement $branch)
	{
		while ($branch->firstChild->firstChild)
			$branch->appendChild($branch->firstChild->removeChild($branch->firstChild->firstChild));
		$branch->removeChild($branch->firstChild);
	}
	protected function getAttributes(DOMElement $element)
	{
		$attributes = array();
		foreach ($element->attributes as $attribute)
		{
			$key = $attribute->namespaceURI . '#' . $attribute->nodeName;
			$attributes[$key] = $attribute->nodeValue;
		}
		return $attributes;
	}
	protected function getBranches()
	{
		$query = 'xsl:when|xsl:otherwise';
		$nodes = array();
		foreach ($this->xpath->query($query, $this->choose) as $node)
			$nodes[] = $node;
		return $nodes;
	}
	protected function hasNoContent()
	{
		$query = 'count(xsl:when/node() | xsl:otherwise/node())';
		return !$this->xpath->evaluate($query, $this->choose);
	}
	protected function hasOtherwise()
	{
		return (bool) $this->xpath->evaluate('count(xsl:otherwise)', $this->choose);
	}
	protected function isEqualNode(DOMNode $node1, DOMNode $node2)
	{
		return ($node1->ownerDocument->saveXML($node1) === $node2->ownerDocument->saveXML($node2));
	}
	protected function isEqualTag(DOMElement $el1, DOMElement $el2)
	{
		return ($el1->namespaceURI === $el2->namespaceURI && $el1->nodeName === $el2->nodeName && $this->getAttributes($el1) === $this->getAttributes($el2));
	}
	protected function matchBranches($childType)
	{
		$branches = $this->getBranches();
		if (!isset($branches[0]->$childType))
			return \false;
		$childNode = $branches[0]->$childType;
		foreach ($branches as $branch)
			if (!isset($branch->$childType) || !$this->isEqualNode($childNode, $branch->$childType))
				return \false;
		return \true;
	}
	protected function matchOnlyChild()
	{
		$branches = $this->getBranches();
		if (!isset($branches[0]->firstChild))
			return \false;
		$firstChild = $branches[0]->firstChild;
		foreach ($branches as $branch)
		{
			if ($branch->childNodes->length !== 1 || !($branch->firstChild instanceof DOMElement))
				return \false;
			if (!$this->isEqualTag($firstChild, $branch->firstChild))
				return \false;
		}
		return \true;
	}
	protected function moveFirstChildBefore()
	{
		$branches = $this->getBranches();
		$this->choose->parentNode->insertBefore(\array_pop($branches)->firstChild, $this->choose);
		foreach ($branches as $branch)
			$branch->removeChild($branch->firstChild);
	}
	protected function moveLastChildAfter()
	{
		$branches = $this->getBranches();
		$node     = \array_pop($branches)->lastChild;
		if (isset($this->choose->nextSibling))
			$this->choose->parentNode->insertBefore($node, $this->choose->nextSibling);
		else
			$this->choose->parentNode->appendChild($node);
		foreach ($branches as $branch)
			$branch->removeChild($branch->lastChild);
	}
	protected function optimizeChooseElement()
	{
		if ($this->hasOtherwise())
		{
			$this->optimizeCommonFirstChild();
			$this->optimizeCommonLastChild();
			$this->optimizeCommonOnlyChild();
			$this->optimizeEmptyOtherwise();
		}
		if ($this->hasNoContent())
			$this->choose->parentNode->removeChild($this->choose);
		else
			$this->optimizeSingleBranch();
	}
	protected function optimizeCommonFirstChild()
	{
		while ($this->matchBranches('firstChild'))
			$this->moveFirstChildBefore();
	}
	protected function optimizeCommonLastChild()
	{
		while ($this->matchBranches('lastChild'))
			$this->moveLastChildAfter();
	}
	protected function optimizeCommonOnlyChild()
	{
		while ($this->matchOnlyChild())
			$this->reparentChild();
	}
	protected function optimizeEmptyOtherwise()
	{
		$query = 'xsl:otherwise[count(node()) = 0]';
		foreach ($this->xpath->query($query, $this->choose) as $otherwise)
			$this->choose->removeChild($otherwise);
	}
	protected function optimizeSingleBranch()
	{
		$query = 'count(xsl:when) = 1 and not(xsl:otherwise)';
		if (!$this->xpath->evaluate($query, $this->choose))
			return;
		$when = $this->xpath->query('xsl:when', $this->choose)->item(0);
		$if   = $this->choose->ownerDocument->createElementNS(self::XMLNS_XSL, 'xsl:if');
		$if->setAttribute('test', $when->getAttribute('test'));
		while ($when->firstChild)
			$if->appendChild($when->removeChild($when->firstChild));
		$this->choose->parentNode->replaceChild($if, $this->choose);
	}
	protected function reparentChild()
	{
		$branches  = $this->getBranches();
		$childNode = $branches[0]->firstChild->cloneNode();
		$childNode->appendChild($this->choose->parentNode->replaceChild($childNode, $this->choose));
		foreach ($branches as $branch)
			$this->adoptChildren($branch);
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;
use DOMElement;
use DOMXPath;
use s9e\TextFormatter\Configurator\TemplateNormalization;
class OptimizeConditionalAttributes extends TemplateNormalization
{
	public function normalize(DOMElement $template)
	{
		$dom   = $template->ownerDocument;
		$xpath = new DOMXPath($dom);
		$query = '//xsl:if'
		       . "[starts-with(@test, '@')]"
		       . '[count(descendant::node()) = 2][xsl:attribute[@name = substring(../@test, 2)][xsl:value-of[@select = ../../@test]]]';
		foreach ($xpath->query($query) as $if)
		{
			$copyOf = $dom->createElementNS(self::XMLNS_XSL, 'xsl:copy-of');
			$copyOf->setAttribute('select', $if->getAttribute('test'));
			$if->parentNode->replaceChild($copyOf, $if);
		}
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;
use DOMElement;
use DOMXPath;
use s9e\TextFormatter\Configurator\TemplateNormalization;
class OptimizeConditionalValueOf extends TemplateNormalization
{
	public function normalize(DOMElement $template)
	{
		$xpath = new DOMXPath($template->ownerDocument);
		$query = '//xsl:if[count(descendant::node()) = 1]/xsl:value-of';
		foreach ($xpath->query($query) as $valueOf)
		{
			$if     = $valueOf->parentNode;
			$test   = $if->getAttribute('test');
			$select = $valueOf->getAttribute('select');
			if ($select !== $test
			 || !\preg_match('#^@[-\\w]+$#D', $select))
				continue;
			$if->parentNode->replaceChild(
				$if->removeChild($valueOf),
				$if
			);
		}
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;
use DOMElement;
use DOMXPath;
use s9e\TextFormatter\Configurator\TemplateNormalization;
class PreserveSingleSpaces extends TemplateNormalization
{
	public function normalize(DOMElement $template)
	{
		$dom   = $template->ownerDocument;
		$xpath = new DOMXPath($dom);
		$query = '//text()[. = " "][not(parent::xsl:text)]';
		foreach ($xpath->query($query) as $textNode)
			$textNode->parentNode->replaceChild(
				$dom->createElementNS(self::XMLNS_XSL, 'text', ' '),
				$textNode
			);
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;
use DOMElement;
use DOMXPath;
use s9e\TextFormatter\Configurator\TemplateNormalization;
class RemoveComments extends TemplateNormalization
{
	public function normalize(DOMElement $template)
	{
		$xpath = new DOMXPath($template->ownerDocument);
		foreach ($xpath->query('//comment()') as $comment)
			$comment->parentNode->removeChild($comment);
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;
use DOMElement;
use DOMXPath;
use s9e\TextFormatter\Configurator\TemplateNormalization;
class RemoveInterElementWhitespace extends TemplateNormalization
{
	public function normalize(DOMElement $template)
	{
		$xpath = new DOMXPath($template->ownerDocument);
		$query = '//text()[normalize-space() = ""][. != " "][not(parent::xsl:text)]';
		foreach ($xpath->query($query) as $textNode)
			$textNode->parentNode->removeChild($textNode);
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;
use DOMElement;
use DOMNodeList;
use s9e\TextFormatter\Configurator\TemplateNormalization;
class SetRelNoreferrerOnTargetedLinks extends TemplateNormalization
{
	public function normalize(DOMElement $template)
	{
		$this->normalizeElements($template->ownerDocument->getElementsByTagName('a'));
		$this->normalizeElements($template->ownerDocument->getElementsByTagName('area'));
	}
	protected function addRelAttribute(DOMElement $element)
	{
		$rel = $element->getAttribute('rel');
		if (\preg_match('(\\S$)', $rel))
			$rel .= ' ';
		$rel .= 'noreferrer';
		$element->setAttribute('rel', $rel);
	}
	protected function linkTargetCanAccessOpener(DOMElement $element)
	{
		if (!$element->hasAttribute('target'))
			return \false;
		if (\preg_match('(\\bno(?:open|referr)er\\b)', $element->getAttribute('rel')))
			return \false;
		return \true;
	}
	protected function normalizeElements(DOMNodeList $elements)
	{
		foreach ($elements as $element)
			if ($this->linkTargetCanAccessOpener($element))
				$this->addRelAttribute($element);
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator;
use RuntimeException;
use s9e\TextFormatter\Configurator\Collections\HostnameList;
use s9e\TextFormatter\Configurator\Collections\SchemeList;
use s9e\TextFormatter\Configurator\Helpers\ConfigHelper;
class UrlConfig implements ConfigProvider
{
	protected $allowedSchemes;
	protected $disallowedHosts;
	protected $restrictedHosts;
	public function __construct()
	{
		$this->disallowedHosts = new HostnameList;
		$this->restrictedHosts = new HostnameList;
		$this->allowedSchemes   = new SchemeList;
		$this->allowedSchemes[] = 'http';
		$this->allowedSchemes[] = 'https';
	}
	public function asConfig()
	{
		return ConfigHelper::toArray(\get_object_vars($this));
	}
	public function allowScheme($scheme)
	{
		if (\strtolower($scheme) === 'javascript')
			throw new RuntimeException('The JavaScript URL scheme cannot be allowed');
		$this->allowedSchemes[] = $scheme;
	}
	public function disallowHost($host, $matchSubdomains = \true)
	{
		$this->disallowedHosts[] = $host;
		if ($matchSubdomains && \substr($host, 0, 1) !== '*')
			$this->disallowedHosts[] = '*.' . $host;
	}
	public function disallowScheme($scheme)
	{
		$this->allowedSchemes->remove($scheme);
	}
	public function getAllowedSchemes()
	{
		return \iterator_to_array($this->allowedSchemes);
	}
	public function restrictHost($host, $matchSubdomains = \true)
	{
		$this->restrictedHosts[] = $host;
		if ($matchSubdomains && \substr($host, 0, 1) !== '*')
			$this->restrictedHosts[] = '*.' . $host;
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Collections;
use InvalidArgumentException;
use s9e\TextFormatter\Configurator\Helpers\RegexpParser;
use s9e\TextFormatter\Configurator\Items\AttributePreprocessor;
use s9e\TextFormatter\Configurator\Items\Regexp;
use s9e\TextFormatter\Configurator\JavaScript\RegexpConvertor;
use s9e\TextFormatter\Configurator\Validators\AttributeName;
class AttributePreprocessorCollection extends Collection
{
	public function add($attrName, $regexp)
	{
		$attrName = AttributeName::normalize($attrName);
		$k = \serialize(array($attrName, $regexp));
		$this->items[$k] = new AttributePreprocessor($regexp);
		return $this->items[$k];
	}
	public function key()
	{
		list($attrName) = \unserialize(\key($this->items));
		return $attrName;
	}
	public function merge($attributePreprocessors)
	{
		$error = \false;
		if ($attributePreprocessors instanceof AttributePreprocessorCollection)
			foreach ($attributePreprocessors as $attrName => $attributePreprocessor)
				$this->add($attrName, $attributePreprocessor->getRegexp());
		elseif (\is_array($attributePreprocessors))
		{
			foreach ($attributePreprocessors as $values)
			{
				if (!\is_array($values))
				{
					$error = \true;
					break;
				}
				list($attrName, $value) = $values;
				if ($value instanceof AttributePreprocessor)
					$value = $value->getRegexp();
				$this->add($attrName, $value);
			}
		}
		else
			$error = \true;
		if ($error)
			throw new InvalidArgumentException('merge() expects an instance of AttributePreprocessorCollection or a 2D array where each element is a [attribute name, regexp] pair');
	}
	public function asConfig()
	{
		$config = array();
		foreach ($this->items as $k => $ap)
		{
			list($attrName) = \unserialize($k);
			$config[] = array($attrName, $ap, $ap->getCaptureNames());
		}
		return $config;
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Collections;
use ArrayAccess;
use InvalidArgumentException;
use RuntimeException;
class NormalizedCollection extends Collection implements ArrayAccess
{
	protected $onDuplicateAction = 'error';
	public function asConfig()
	{
		$config = parent::asConfig();
		\ksort($config);
		return $config;
	}
	public function onDuplicate($action = \null)
	{
		$old = $this->onDuplicateAction;
		if (\func_num_args() && $action !== 'error' && $action !== 'ignore' && $action !== 'replace')
			throw new InvalidArgumentException("Invalid onDuplicate action '" . $action . "'. Expected: 'error', 'ignore' or 'replace'");
		$this->onDuplicateAction = $action;
		return $old;
	}
	protected function getAlreadyExistsException($key)
	{
		return new RuntimeException("Item '" . $key . "' already exists");
	}
	protected function getNotExistException($key)
	{
		return new RuntimeException("Item '" . $key . "' does not exist");
	}
	public function normalizeKey($key)
	{
		return $key;
	}
	public function normalizeValue($value)
	{
		return $value;
	}
	public function add($key, $value = \null)
	{
		if ($this->exists($key))
			if ($this->onDuplicateAction === 'ignore')
				return $this->get($key);
			elseif ($this->onDuplicateAction === 'error')
				throw $this->getAlreadyExistsException($key);
		return $this->set($key, $value);
	}
	public function contains($value)
	{
		return \in_array($this->normalizeValue($value), $this->items);
	}
	public function delete($key)
	{
		$key = $this->normalizeKey($key);
		unset($this->items[$key]);
	}
	public function exists($key)
	{
		$key = $this->normalizeKey($key);
		return \array_key_exists($key, $this->items);
	}
	public function get($key)
	{
		if (!$this->exists($key))
			throw $this->getNotExistException($key);
		$key = $this->normalizeKey($key);
		return $this->items[$key];
	}
	public function indexOf($value)
	{
		return \array_search($this->normalizeValue($value), $this->items);
	}
	public function set($key, $value)
	{
		$key = $this->normalizeKey($key);
		$this->items[$key] = $this->normalizeValue($value);
		return $this->items[$key];
	}
	public function offsetExists($offset)
	{
		return $this->exists($offset);
	}
	public function offsetGet($offset)
	{
		return $this->get($offset);
	}
	public function offsetSet($offset, $value)
	{
		$this->set($offset, $value);
	}
	public function offsetUnset($offset)
	{
		$this->delete($offset);
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Collections;
use ArrayAccess;
use InvalidArgumentException;
use RuntimeException;
use s9e\TextFormatter\Configurator\ConfigProvider;
use s9e\TextFormatter\Configurator\JavaScript\Dictionary;
use s9e\TextFormatter\Configurator\Validators\TagName;
use s9e\TextFormatter\Parser;
class Ruleset extends Collection implements ArrayAccess, ConfigProvider
{
	public function __construct()
	{
		$this->clear();
	}
	public function clear()
	{
		parent::clear();
		$this->defaultChildRule('allow');
		$this->defaultDescendantRule('allow');
	}
	public function offsetExists($k)
	{
		return isset($this->items[$k]);
	}
	public function offsetGet($k)
	{
		return $this->items[$k];
	}
	public function offsetSet($k, $v)
	{
		throw new RuntimeException('Not supported');
	}
	public function offsetUnset($k)
	{
		return $this->remove($k);
	}
	public function asConfig()
	{
		$config = $this->items;
		unset($config['allowChild']);
		unset($config['allowDescendant']);
		unset($config['defaultChildRule']);
		unset($config['defaultDescendantRule']);
		unset($config['denyChild']);
		unset($config['denyDescendant']);
		unset($config['requireParent']);
		$bitValues = array(
			'autoClose'                   => Parser::RULE_AUTO_CLOSE,
			'autoReopen'                  => Parser::RULE_AUTO_REOPEN,
			'breakParagraph'              => Parser::RULE_BREAK_PARAGRAPH,
			'createParagraphs'            => Parser::RULE_CREATE_PARAGRAPHS,
			'disableAutoLineBreaks'       => Parser::RULE_DISABLE_AUTO_BR,
			'enableAutoLineBreaks'        => Parser::RULE_ENABLE_AUTO_BR,
			'ignoreSurroundingWhitespace' => Parser::RULE_IGNORE_WHITESPACE,
			'ignoreTags'                  => Parser::RULE_IGNORE_TAGS,
			'ignoreText'                  => Parser::RULE_IGNORE_TEXT,
			'isTransparent'               => Parser::RULE_IS_TRANSPARENT,
			'preventLineBreaks'           => Parser::RULE_PREVENT_BR,
			'suspendAutoLineBreaks'       => Parser::RULE_SUSPEND_AUTO_BR,
			'trimFirstLine'               => Parser::RULE_TRIM_FIRST_LINE
		);
		$bitfield = 0;
		foreach ($bitValues as $ruleName => $bitValue)
		{
			if (!empty($config[$ruleName]))
				$bitfield |= $bitValue;
			unset($config[$ruleName]);
		}
		foreach (array('closeAncestor', 'closeParent', 'fosterParent') as $ruleName)
			if (isset($config[$ruleName]))
			{
				$targets = \array_fill_keys($config[$ruleName], 1);
				$config[$ruleName] = new Dictionary($targets);
			}
		$config['flags'] = $bitfield;
		return $config;
	}
	public function merge($rules, $overwrite = \true)
	{
		if (!\is_array($rules)
		 && !($rules instanceof self))
			throw new InvalidArgumentException('merge() expects an array or an instance of Ruleset');
		foreach ($rules as $action => $value)
			if (\is_array($value))
				foreach ($value as $tagName)
					$this->$action($tagName);
			elseif ($overwrite || !isset($this->items[$action]))
				$this->$action($value);
	}
	public function remove($type, $tagName = \null)
	{
		if (\preg_match('(^default(?:Child|Descendant)Rule)', $type))
			throw new RuntimeException('Cannot remove ' . $type);
		if (isset($tagName))
		{
			$tagName = TagName::normalize($tagName);
			if (isset($this->items[$type]))
			{
				$this->items[$type] = \array_diff(
					$this->items[$type],
					array($tagName)
				);
				if (empty($this->items[$type]))
					unset($this->items[$type]);
				else
					$this->items[$type] = \array_values($this->items[$type]);
			}
		}
		else
			unset($this->items[$type]);
	}
	protected function addBooleanRule($ruleName, $bool)
	{
		if (!\is_bool($bool))
			throw new InvalidArgumentException($ruleName . '() expects a boolean');
		$this->items[$ruleName] = $bool;
		return $this;
	}
	protected function addTargetedRule($ruleName, $tagName)
	{
		$this->items[$ruleName][] = TagName::normalize($tagName);
		return $this;
	}
	public function allowChild($tagName)
	{
		return $this->addTargetedRule('allowChild', $tagName);
	}
	public function allowDescendant($tagName)
	{
		return $this->addTargetedRule('allowDescendant', $tagName);
	}
	public function autoClose($bool = \true)
	{
		return $this->addBooleanRule('autoClose', $bool);
	}
	public function autoReopen($bool = \true)
	{
		return $this->addBooleanRule('autoReopen', $bool);
	}
	public function breakParagraph($bool = \true)
	{
		return $this->addBooleanRule('breakParagraph', $bool);
	}
	public function closeAncestor($tagName)
	{
		return $this->addTargetedRule('closeAncestor', $tagName);
	}
	public function closeParent($tagName)
	{
		return $this->addTargetedRule('closeParent', $tagName);
	}
	public function createChild($tagName)
	{
		return $this->addTargetedRule('createChild', $tagName);
	}
	public function createParagraphs($bool = \true)
	{
		return $this->addBooleanRule('createParagraphs', $bool);
	}
	public function defaultChildRule($rule)
	{
		if ($rule !== 'allow' && $rule !== 'deny')
			throw new InvalidArgumentException("defaultChildRule() only accepts 'allow' or 'deny'");
		$this->items['defaultChildRule'] = $rule;
		return $this;
	}
	public function defaultDescendantRule($rule)
	{
		if ($rule !== 'allow' && $rule !== 'deny')
			throw new InvalidArgumentException("defaultDescendantRule() only accepts 'allow' or 'deny'");
		$this->items['defaultDescendantRule'] = $rule;
		return $this;
	}
	public function denyChild($tagName)
	{
		return $this->addTargetedRule('denyChild', $tagName);
	}
	public function denyDescendant($tagName)
	{
		return $this->addTargetedRule('denyDescendant', $tagName);
	}
	public function disableAutoLineBreaks($bool = \true)
	{
		return $this->addBooleanRule('disableAutoLineBreaks', $bool);
	}
	public function enableAutoLineBreaks($bool = \true)
	{
		return $this->addBooleanRule('enableAutoLineBreaks', $bool);
	}
	public function fosterParent($tagName)
	{
		return $this->addTargetedRule('fosterParent', $tagName);
	}
	public function ignoreSurroundingWhitespace($bool = \true)
	{
		return $this->addBooleanRule('ignoreSurroundingWhitespace', $bool);
	}
	public function ignoreTags($bool = \true)
	{
		return $this->addBooleanRule('ignoreTags', $bool);
	}
	public function ignoreText($bool = \true)
	{
		return $this->addBooleanRule('ignoreText', $bool);
	}
	public function isTransparent($bool = \true)
	{
		return $this->addBooleanRule('isTransparent', $bool);
	}
	public function preventLineBreaks($bool = \true)
	{
		return $this->addBooleanRule('preventLineBreaks', $bool);
	}
	public function requireParent($tagName)
	{
		return $this->addTargetedRule('requireParent', $tagName);
	}
	public function requireAncestor($tagName)
	{
		return $this->addTargetedRule('requireAncestor', $tagName);
	}
	public function suspendAutoLineBreaks($bool = \true)
	{
		return $this->addBooleanRule('suspendAutoLineBreaks', $bool);
	}
	public function trimFirstLine($bool = \true)
	{
		return $this->addBooleanRule('trimFirstLine', $bool);
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Items;
abstract class Filter extends ProgrammableCallback
{
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateChecks;
use DOMElement;
use s9e\TextFormatter\Configurator\Helpers\TemplateHelper;
use s9e\TextFormatter\Configurator\Helpers\XPathHelper;
use s9e\TextFormatter\Configurator\Items\Attribute;
class DisallowUnsafeDynamicCSS extends AbstractDynamicContentCheck
{
	protected function getNodes(DOMElement $template)
	{
		return TemplateHelper::getCSSNodes($template->ownerDocument);
	}
	protected function isExpressionSafe($expr)
	{
		return XPathHelper::isExpressionNumeric($expr);
	}
	protected function isSafe(Attribute $attribute)
	{
		return $attribute->isSafeInCSS();
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateChecks;
use DOMElement;
use s9e\TextFormatter\Configurator\Helpers\XPathHelper;
use s9e\TextFormatter\Configurator\Helpers\TemplateHelper;
use s9e\TextFormatter\Configurator\Items\Attribute;
class DisallowUnsafeDynamicJS extends AbstractDynamicContentCheck
{
	protected function getNodes(DOMElement $template)
	{
		return TemplateHelper::getJSNodes($template->ownerDocument);
	}
	protected function isExpressionSafe($expr)
	{
		return XPathHelper::isExpressionNumeric($expr);
	}
	protected function isSafe(Attribute $attribute)
	{
		return $attribute->isSafeInJS();
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateChecks;
use DOMAttr;
use DOMElement;
use DOMText;
use s9e\TextFormatter\Configurator\Helpers\TemplateHelper;
use s9e\TextFormatter\Configurator\Items\Attribute;
use s9e\TextFormatter\Configurator\Items\Tag;
class DisallowUnsafeDynamicURL extends AbstractDynamicContentCheck
{
	protected $exceptionRegexp = '(^(?:(?!data|\\w*script)\\w+:|[^:]*/|#))i';
	protected function getNodes(DOMElement $template)
	{
		return TemplateHelper::getURLNodes($template->ownerDocument);
	}
	protected function isSafe(Attribute $attribute)
	{
		return $attribute->isSafeAsURL();
	}
	protected function checkAttributeNode(DOMAttr $attribute, Tag $tag)
	{
		if (\preg_match($this->exceptionRegexp, $attribute->value))
			return;
		parent::checkAttributeNode($attribute, $tag);
	}
	protected function checkElementNode(DOMElement $element, Tag $tag)
	{
		if ($element->firstChild
		 && $element->firstChild instanceof DOMText
		 && \preg_match($this->exceptionRegexp, $element->firstChild->textContent))
			return;
		parent::checkElementNode($element, $tag);
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateChecks;
class RestrictFlashScriptAccess extends AbstractFlashRestriction
{
	public $defaultSetting = 'sameDomain';
	protected $settingName = 'allowScriptAccess';
	protected $settings = array(
		'always'     => 3,
		'samedomain' => 2,
		'never'      => 1
	);
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;
use DOMDocument;
use DOMXPath;
class FoldArithmeticConstants extends AbstractConstantFolding
{
	protected $xpath;
	public function __construct()
	{
		$this->xpath = new DOMXPath(new DOMDocument);
	}
	protected function getOptimizationPasses()
	{
		return array(
			'(^[-+0-9\\s]+$)'                        => 'foldOperation',
			'( \\+ 0(?! [^+\\)])|(?<![-\\w])0 \\+ )' => 'foldAdditiveIdentity',
			'(^((?>\\d+ [-+] )*)(\\d+) div (\\d+))'  => 'foldDivision',
			'(^((?>\\d+ [-+] )*)(\\d+) \\* (\\d+))'  => 'foldMultiplication',
			'(\\( \\d+ (?>(?>[-+*]|div) \\d+ )+\\))' => 'foldSubExpression',
			'((?<=[-+*\\(]|\\bdiv|^) \\( ([@$][-\\w]+|\\d+(?>\\.\\d+)?) \\) (?=[-+*\\)]|div|$))' => 'removeParentheses'
		);
	}
	public function evaluateExpression($expr)
	{
		$expr = \preg_replace_callback(
			'(([\'"])(.*?)\\1)s',
			function ($m)
			{
				return $m[1] . \bin2hex($m[2]) . $m[1];
			},
			$expr
		);
		$expr = parent::evaluateExpression($expr);
		$expr = \preg_replace_callback(
			'(([\'"])(.*?)\\1)s',
			function ($m)
			{
				return $m[1] . \pack('H*', $m[2]) . $m[1];
			},
			$expr
		);
		return $expr;
	}
	protected function foldAdditiveIdentity(array $m)
	{
		return '';
	}
	protected function foldDivision(array $m)
	{
		return $m[1] . ($m[2] / $m[3]);
	}
	protected function foldMultiplication(array $m)
	{
		return $m[1] . ($m[2] * $m[3]);
	}
	protected function foldOperation(array $m)
	{
		return (string) $this->xpath->evaluate($m[0]);
	}
	protected function foldSubExpression(array $m)
	{
		return '(' . $this->evaluateExpression(\trim(\substr($m[0], 1, -1))) . ')';
	}
	protected function removeParentheses(array $m)
	{
		return ' ' . $m[1] . ' ';
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;
use DOMDocument;
use DOMXPath;
use s9e\TextFormatter\Configurator\Helpers\XPathHelper;
class FoldConstantXPathExpressions extends AbstractConstantFolding
{
	protected $supportedFunctions = array(
		'ceiling',
		'concat',
		'contains',
		'floor',
		'normalize-space',
		'number',
		'round',
		'starts-with',
		'string',
		'string-length',
		'substring',
		'substring-after',
		'substring-before',
		'sum',
		'translate'
	);
	protected $xpath;
	public function __construct()
	{
		$this->xpath = new DOMXPath(new DOMDocument);
	}
	protected function getOptimizationPasses()
	{
		return array(
			'(^(?:"[^"]*"|\'[^\']*\'|\\.[0-9]|[^"$&\'./:<=>@[\\]])++$)' => 'foldConstantXPathExpression'
		);
	}
	protected function canBeSerialized($value)
	{
		return (\is_string($value) || \is_integer($value) || \is_float($value));
	}
	protected function evaluate($expr)
	{
		$useErrors = \libxml_use_internal_errors(\true);
		$result    = $this->xpath->evaluate($expr);
		\libxml_use_internal_errors($useErrors);
		return $result;
	}
	protected function foldConstantXPathExpression(array $m)
	{
		$expr = $m[0];
		if ($this->isConstantExpression($expr))
		{
			$result = $this->evaluate($expr);
			if ($this->canBeSerialized($result))
			{
				$foldedExpr = XPathHelper::export($result);
				if (\strlen($foldedExpr) < \strlen($expr))
					$expr = $foldedExpr;
			}
		}
		return $expr;
	}
	protected function isConstantExpression($expr)
	{
		$expr = \preg_replace('("[^"]*"|\'[^\']*\')', '', $expr);
		\preg_match_all('(\\w[-\\w]+(?=\\())', $expr, $m);
		if (\count(\array_diff($m[0], $this->supportedFunctions)) > 0)
			return \false;
		return !\preg_match('([^\\s\\-0-9a-z\\(-.]|\\.(?![0-9])|\\b[-a-z](?![-\\w]+\\())i', $expr);
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Collections;
use RuntimeException;
use s9e\TextFormatter\Configurator\Items\Attribute;
use s9e\TextFormatter\Configurator\Validators\AttributeName;
class AttributeCollection extends NormalizedCollection
{
	protected $onDuplicateAction = 'replace';
	protected function getAlreadyExistsException($key)
	{
		return new RuntimeException("Attribute '" . $key . "' already exists");
	}
	protected function getNotExistException($key)
	{
		return new RuntimeException("Attribute '" . $key . "' does not exist");
	}
	public function normalizeKey($key)
	{
		return AttributeName::normalize($key);
	}
	public function normalizeValue($value)
	{
		return ($value instanceof Attribute)
		     ? $value
		     : new Attribute($value);
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Collections;
use InvalidArgumentException;
use s9e\TextFormatter\Configurator\Items\AttributeFilter;
class AttributeFilterCollection extends NormalizedCollection
{
	public function get($key)
	{
		$key = $this->normalizeKey($key);
		if (!$this->exists($key))
			if ($key[0] === '#')
				$this->set($key, self::getDefaultFilter(\substr($key, 1)));
			else
				$this->set($key, new AttributeFilter($key));
		$filter = parent::get($key);
		$filter = clone $filter;
		return $filter;
	}
	public static function getDefaultFilter($filterName)
	{
		$filterName = \ucfirst(\strtolower($filterName));
		$className  = 's9e\\TextFormatter\\Configurator\\Items\\AttributeFilters\\' . $filterName . 'Filter';
		if (!\class_exists($className))
			throw new InvalidArgumentException("Unknown attribute filter '" . $filterName . "'");
		return new $className;
	}
	public function normalizeKey($key)
	{
		if (\preg_match('/^#[a-z_0-9]+$/Di', $key))
			return \strtolower($key);
		if (\is_string($key) && \is_callable($key))
			return $key;
		throw new InvalidArgumentException("Invalid filter name '" . $key . "'");
	}
	public function normalizeValue($value)
	{
		if ($value instanceof AttributeFilter)
			return $value;
		if (\is_callable($value))
			return new AttributeFilter($value);
		throw new InvalidArgumentException('Argument 1 passed to ' . __METHOD__ . ' must be a valid callback or an instance of s9e\\TextFormatter\\Configurator\\Items\\AttributeFilter');
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Collections;
use InvalidArgumentException;
class NormalizedList extends NormalizedCollection
{
	public function add($value, $void = \null)
	{
		return $this->append($value);
	}
	public function append($value)
	{
		$value = $this->normalizeValue($value);
		$this->items[] = $value;
		return $value;
	}
	public function delete($key)
	{
		parent::delete($key);
		$this->items = \array_values($this->items);
	}
	public function insert($offset, $value)
	{
		$offset = $this->normalizeKey($offset);
		$value  = $this->normalizeValue($value);
		\array_splice($this->items, $offset, 0, array($value));
		return $value;
	}
	public function normalizeKey($key)
	{
		$normalizedKey = \filter_var(
			(\preg_match('(^-\\d+$)D', $key)) ? \count($this->items) + $key : $key,
			\FILTER_VALIDATE_INT,
			array(
				'options' => array(
					'min_range' => 0,
					'max_range' => \count($this->items)
				)
			)
		);
		if ($normalizedKey === \false)
			throw new InvalidArgumentException("Invalid offset '" . $key . "'");
		return $normalizedKey;
	}
	public function offsetSet($offset, $value)
	{
		if ($offset === \null)
			$this->append($value);
		else
			parent::offsetSet($offset, $value);
	}
	public function prepend($value)
	{
		$value = $this->normalizeValue($value);
		\array_unshift($this->items, $value);
		return $value;
	}
	public function remove($value)
	{
		$keys = \array_keys($this->items, $this->normalizeValue($value));
		foreach ($keys as $k)
			unset($this->items[$k]);
		$this->items = \array_values($this->items);
		return \count($keys);
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Collections;
use InvalidArgumentException;
use RuntimeException;
use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Plugins\ConfiguratorBase;
class PluginCollection extends NormalizedCollection
{
	protected $configurator;
	public function __construct(Configurator $configurator)
	{
		$this->configurator = $configurator;
	}
	public function finalize()
	{
		foreach ($this->items as $plugin)
			$plugin->finalize();
	}
	public function normalizeKey($pluginName)
	{
		if (!\preg_match('#^[A-Z][A-Za-z_0-9]+$#D', $pluginName))
			throw new InvalidArgumentException("Invalid plugin name '" . $pluginName . "'");
		return $pluginName;
	}
	public function normalizeValue($value)
	{
		if (\is_string($value) && \class_exists($value))
			$value = new $value($this->configurator);
		if ($value instanceof ConfiguratorBase)
			return $value;
		throw new InvalidArgumentException('PluginCollection::normalizeValue() expects a class name or an object that implements s9e\\TextFormatter\\Plugins\\ConfiguratorBase');
	}
	public function load($pluginName, array $overrideProps = array())
	{
		$pluginName = $this->normalizeKey($pluginName);
		$className  = 's9e\\TextFormatter\\Plugins\\' . $pluginName . '\\Configurator';
		if (!\class_exists($className))
			throw new RuntimeException("Class '" . $className . "' does not exist");
		$plugin = new $className($this->configurator, $overrideProps);
		$this->set($pluginName, $plugin);
		return $plugin;
	}
	public function asConfig()
	{
		$plugins = parent::asConfig();
		foreach ($plugins as $pluginName => &$pluginConfig)
		{
			$plugin = $this->get($pluginName);
			$pluginConfig += $plugin->getBaseProperties();
			if ($pluginConfig['quickMatch'] === \false)
				unset($pluginConfig['quickMatch']);
			if (!isset($pluginConfig['regexp']))
				unset($pluginConfig['regexpLimit']);
			$className = 's9e\\TextFormatter\\Plugins\\' . $pluginName . '\\Parser';
			if ($pluginConfig['className'] === $className)
				unset($pluginConfig['className']);
		}
		unset($pluginConfig);
		return $plugins;
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Collections;
use RuntimeException;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\Validators\TagName;
class TagCollection extends NormalizedCollection
{
	protected $onDuplicateAction = 'replace';
	protected function getAlreadyExistsException($key)
	{
		return new RuntimeException("Tag '" . $key . "' already exists");
	}
	protected function getNotExistException($key)
	{
		return new RuntimeException("Tag '" . $key . "' does not exist");
	}
	public function normalizeKey($key)
	{
		return TagName::normalize($key);
	}
	public function normalizeValue($value)
	{
		return ($value instanceof Tag)
		     ? $value
		     : new Tag($value);
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Collections;
use s9e\TextFormatter\Configurator\Validators\TemplateParameterName;
class TemplateParameterCollection extends NormalizedCollection
{
	public function normalizeKey($key)
	{
		return TemplateParameterName::normalize($key);
	}
	public function normalizeValue($value)
	{
		return (string) $value;
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Items;
use s9e\TextFormatter\Configurator\Traits\TemplateSafeness;
class AttributeFilter extends Filter
{
	protected $markedSafe = array();
	protected function isSafe($context)
	{
		return !empty($this->markedSafe[$context]);
	}
	public function isSafeAsURL()
	{
		return $this->isSafe('AsURL');
	}
	public function isSafeInCSS()
	{
		return $this->isSafe('InCSS');
	}

	public function markAsSafeAsURL()
	{
		$this->markedSafe['AsURL'] = \true;
		return $this;
	}
	public function markAsSafeInCSS()
	{
		$this->markedSafe['InCSS'] = \true;
		return $this;
	}
	public function markAsSafeInJS()
	{
		$this->markedSafe['InJS'] = \true;
		return $this;
	}
	public function resetSafeness()
	{
		$this->markedSafe = array();
		return $this;
	}
	public function __construct($callback)
	{
		parent::__construct($callback);
		$this->resetParameters();
		$this->addParameterByName('attrValue');
	}
	public function isSafeInJS()
	{
		$safeCallbacks = array(
			'urlencode',
			'strtotime',
			'rawurlencode'
		);
		if (\in_array($this->callback, $safeCallbacks, \true))
			return \true;
		return $this->isSafe('InJS');
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Items;
class TagFilter extends Filter
{
	public function __construct($callback)
	{
		parent::__construct($callback);
		$this->resetParameters();
		$this->addParameterByName('tag');
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Collections;
use InvalidArgumentException;
use s9e\TextFormatter\Configurator\Items\ProgrammableCallback;
abstract class FilterChain extends NormalizedList
{
	abstract protected function getFilterClassName();
	public function containsCallback($callback)
	{
		$pc = new ProgrammableCallback($callback);
		$callback = $pc->getCallback();
		foreach ($this->items as $filter)
			if ($callback === $filter->getCallback())
				return \true;
		return \false;
	}
	public function normalizeValue($value)
	{
		$className  = $this->getFilterClassName();
		if ($value instanceof $className)
			return $value;
		if (!\is_callable($value))
			throw new InvalidArgumentException('Filter ' . \var_export($value, \true) . ' is neither callable nor an instance of ' . $className);
		return new $className($value);
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Collections;
use s9e\TextFormatter\Configurator\Helpers\RegexpBuilder;
use s9e\TextFormatter\Configurator\Items\Regexp;
class HostnameList extends NormalizedList
{
	public function asConfig()
	{
		if (empty($this->items))
			return \null;
		return new Regexp($this->getRegexp());
	}
	public function getRegexp()
	{
		$hosts = array();
		foreach ($this->items as $host)
			$hosts[] = $this->normalizeHostmask($host);
		$regexp = RegexpBuilder::fromList(
			$hosts,
			array(
				'specialChars' => array(
					'*' => '.*',
					'^' => '^',
					'$' => '$'
				)
			)
		);
		return '/' . $regexp . '/DSis';
	}
	protected function normalizeHostmask($host)
	{
		if (\preg_match('#[\\x80-\xff]#', $host) && \function_exists('idn_to_ascii'))
			$host = \idn_to_ascii($host);
		if (\substr($host, 0, 1) === '*')
			$host = \ltrim($host, '*');
		else
			$host = '^' . $host;
		if (\substr($host, -1) === '*')
			$host = \rtrim($host, '*');
		else
			$host .= '$';
		return $host;
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Collections;
use InvalidArgumentException;
use s9e\TextFormatter\Configurator\RulesGenerators\Interfaces\BooleanRulesGenerator;
use s9e\TextFormatter\Configurator\RulesGenerators\Interfaces\TargetedRulesGenerator;
class RulesGeneratorList extends NormalizedList
{
	public function normalizeValue($generator)
	{
		if (\is_string($generator))
		{
			$className = 's9e\\TextFormatter\\Configurator\\RulesGenerators\\' . $generator;
			if (\class_exists($className))
				$generator = new $className;
		}
		if (!($generator instanceof BooleanRulesGenerator)
		 && !($generator instanceof TargetedRulesGenerator))
			throw new InvalidArgumentException('Invalid rules generator ' . \var_export($generator, \true));
		return $generator;
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Collections;
use InvalidArgumentException;
use s9e\TextFormatter\Configurator\Helpers\RegexpBuilder;
use s9e\TextFormatter\Configurator\Items\Regexp;
class SchemeList extends NormalizedList
{
	public function asConfig()
	{
		return new Regexp('/^' . RegexpBuilder::fromList($this->items) . '$/Di');
	}
	public function normalizeValue($scheme)
	{
		if (!\preg_match('#^[a-z][a-z0-9+\\-.]*$#Di', $scheme))
			throw new InvalidArgumentException("Invalid scheme name '" . $scheme . "'");
		return \strtolower($scheme);
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Collections;
use s9e\TextFormatter\Configurator\TemplateCheck;
class TemplateCheckList extends NormalizedList
{
	public function normalizeValue($check)
	{
		if (!($check instanceof TemplateCheck))
		{
			$className = 's9e\\TextFormatter\\Configurator\\TemplateChecks\\' . $check;
			$check     = new $className;
		}
		return $check;
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Collections;
use s9e\TextFormatter\Configurator\TemplateNormalization;
use s9e\TextFormatter\Configurator\TemplateNormalizations\Custom;
class TemplateNormalizationList extends NormalizedList
{
	public function normalizeValue($value)
	{
		if ($value instanceof TemplateNormalization)
			return $value;
		if (\is_callable($value))
			return new Custom($value);
		$className = 's9e\\TextFormatter\\Configurator\\TemplateNormalizations\\' . $value;
		return new $className;
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Items\AttributeFilters;
use s9e\TextFormatter\Configurator\Items\AttributeFilter;
class UrlFilter extends AttributeFilter
{
	public function __construct()
	{
		parent::__construct('s9e\\TextFormatter\\Parser\\BuiltInFilters::filterUrl');
		$this->resetParameters();
		$this->addParameterByName('attrValue');
		$this->addParameterByName('urlConfig');
		$this->addParameterByName('logger');
		$this->setJS('BuiltInFilters.filterUrl');
	}
	public function isSafeInCSS()
	{
		return \true;
	}
	public function isSafeInJS()
	{
		return \true;
	}
	public function isSafeAsURL()
	{
		return \true;
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Collections;
class AttributeFilterChain extends FilterChain
{
	public function getFilterClassName()
	{
		return 's9e\\TextFormatter\\Configurator\\Items\\AttributeFilter';
	}
	public function normalizeValue($value)
	{
		if (\is_string($value) && \preg_match('(^#\\w+$)', $value))
			$value = AttributeFilterCollection::getDefaultFilter(\substr($value, 1));
		return parent::normalizeValue($value);
	}
}

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Collections;
class TagFilterChain extends FilterChain
{
	public function getFilterClassName()
	{
		return 's9e\\TextFormatter\\Configurator\\Items\\TagFilter';
	}
}