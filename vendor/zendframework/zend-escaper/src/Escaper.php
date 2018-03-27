<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Escaper;

/**
 * Context specific methods for use in secure output escaping
 */
class Escaper
{
    /**
     * Entity Map mapping Unicode codepoints to any available named HTML entities.
     *
     * While HTML supports far more named entities, the lowest common denominator
     * has become HTML5's XML Serialisation which is restricted to the those named
     * entities that XML supports. Using HTML entities would result in this error:
     *     XML Parsing Error: undefined entity
     *
     * @var array
     */
    protected static $htmlNamedEntityMap = [
        34 => 'quot',         // quotation mark
        38 => 'amp',          // ampersand
        60 => 'lt',           // less-than sign
        62 => 'gt',           // greater-than sign
    ];

    /**
     * Current encoding for escaping. If not UTF-8, we convert strings from this encoding
     * pre-escaping and back to this encoding post-escaping.
     *
     * @var string
     */
    protected $encoding = 'utf-8';

    /**
     * Holds the value of the special flags passed as second parameter to
     * htmlspecialchars().
     *
     * @var int
     */
    protected $htmlSpecialCharsFlags;

    /**
     * Static Matcher which escapes characters for HTML Attribute contexts
     *
     * @var callable
     */
    protected $htmlAttrMatcher;

    /**
     * Static Matcher which escapes characters for Javascript contexts
     *
     * @var callable
     */
    protected $jsMatcher;

    /**
     * Static Matcher which escapes characters for CSS Attribute contexts
     *
     * @var callable
     */
    protected $cssMatcher;

    /**
     * List of all encoding supported by this class
     *
     * @var array
     */
    protected $supportedEncodings = [
        'iso-8859-1',   'iso8859-1',    'iso-8859-5',   'iso8859-5',
        'iso-8859-15',  'iso8859-15',   'utf-8',        'cp866',
        'ibm866',       '866',          'cp1251',       'windows-1251',
        'win-1251',     '1251',         'cp1252',       'windows-1252',
        '1252',         'koi8-r',       'koi8-ru',      'koi8r',
        'big5',         '950',          'gb2312',       '936',
        'big5-hkscs',   'shift_jis',    'sjis',         'sjis-win',
        'cp932',        '932',          'euc-jp',       'eucjp',
        'eucjp-win',    'macroman'
    ];

    /**
     * Constructor: Single parameter allows setting of global encoding for use by
     * the current object.
     *
     * @param string $encoding
     * @throws Exception\InvalidArgumentException
     */
    public function __construct($encoding = null)
    {
        if ($encoding !== null) {
            $encoding = (string) $encoding;
            if ($encoding === '') {
                throw new Exception\InvalidArgumentException(
                    get_class($this) . ' constructor parameter does not allow a blank value'
                );
            }

            $encoding = strtolower($encoding);
            if (!in_array($encoding, $this->supportedEncodings)) {
                throw new Exception\InvalidArgumentException(
                    'Value of \'' . $encoding . '\' passed to ' . get_class($this)
                    . ' constructor parameter is invalid. Provide an encoding supported by htmlspecialchars()'
                );
            }

            $this->encoding = $encoding;
        }

        // We take advantage of ENT_SUBSTITUTE flag to correctly deal with invalid UTF-8 sequences.
        $this->htmlSpecialCharsFlags = ENT_QUOTES | ENT_SUBSTITUTE;

        // set matcher callbacks
        $this->htmlAttrMatcher = [$this, 'htmlAttrMatcher'];
        $this->jsMatcher       = [$this, 'jsMatcher'];
        $this->cssMatcher      = [$this, 'cssMatcher'];
    }

    /**
     * Return the encoding that all output/input is expected to be encoded in.
     *
     * @return string
     */
    public function getEncoding()
    {
        return $this->encoding;
    }

    /**
     * Escape a string for the HTML Body context where there are very few characters
     * of special meaning. Internally this will use htmlspecialchars().
     *
     * @param string $string
     * @return string
     */
    public function escapeHtml($string)
    {
        return htmlspecialchars($string, $this->htmlSpecialCharsFlags, $this->encoding);
    }

    /**
     * Escape a string for the HTML Attribute context. We use an extended set of characters
     * to escape that are not covered by htmlspecialchars() to cover cases where an attribute
     * might be unquoted or quoted illegally (e.g. backticks are valid quotes for IE).
     *
     * @param string $string
     * @return string
     */
    public function escapeHtmlAttr($string)
    {
        $string = $this->toUtf8($string);
        if ($string === '' || ctype_digit($string)) {
            return $string;
        }

        $result = preg_replace_callback('/[^a-z0-9,\.\-_]/iSu', $this->htmlAttrMatcher, $string);
        return $this->fromUtf8($result);
    }

    /**
     * Escape a string for the Javascript context. This does not use json_encode(). An extended
     * set of characters are escaped beyond ECMAScript's rules for Javascript literal string
     * escaping in order to prevent misinterpretation of Javascript as HTML leading to the
     * injection of special characters and entities. The escaping used should be tolerant
     * of cases where HTML escaping was not applied on top of Javascript escaping correctly.
     * Backslash escaping is not used as it still leaves the escaped character as-is and so
     * is not useful in a HTML context.
     *
     * @param string $string
     * @return string
     */
    public function escapeJs($string)
    {
        $string = $this->toUtf8($string);
        if ($string === '' || ctype_digit($string)) {
            return $string;
        }

        $result = preg_replace_callback('/[^a-z0-9,\._]/iSu', $this->jsMatcher, $string);
        return $this->fromUtf8($result);
    }

    /**
     * Escape a string for the URI or Parameter contexts. This should not be used to escape
     * an entire URI - only a subcomponent being inserted. The function is a simple proxy
     * to rawurlencode() which now implements RFC 3986 since PHP 5.3 completely.
     *
     * @param string $string
     * @return string
     */
    public function escapeUrl($string)
    {
        return rawurlencode($string);
    }

    /**
     * Escape a string for the CSS context. CSS escaping can be applied to any string being
     * inserted into CSS and escapes everything except alphanumerics.
     *
     * @param string $string
     * @return string
     */
    public function escapeCss($string)
    {
        $string = $this->toUtf8($string);
        if ($string === '' || ctype_digit($string)) {
            return $string;
        }

        $result = preg_replace_callback('/[^a-z0-9]/iSu', $this->cssMatcher, $string);
        return $this->fromUtf8($result);
    }

    /**
     * Callback function for preg_replace_callback that applies HTML Attribute
     * escaping to all matches.
     *
     * @param array $matches
     * @return string
     */
    protected function htmlAttrMatcher($matches)
    {
        $chr = $matches[0];
        $ord = ord($chr);

        /**
         * The following replaces characters undefined in HTML with the
         * hex entity for the Unicode replacement character.
         */
        if (($ord <= 0x1f && $chr != "\t" && $chr != "\n" && $chr != "\r")
            || ($ord >= 0x7f && $ord <= 0x9f)
        ) {
            return '&#xFFFD;';
        }

        /**
         * Check if the current character to escape has a name entity we should
         * replace it with while grabbing the integer value of the character.
         */
        if (strlen($chr) > 1) {
            $chr = $this->convertEncoding($chr, 'UTF-32BE', 'UTF-8');
        }

        $hex = bin2hex($chr);
        $ord = hexdec($hex);
        if (isset(static::$htmlNamedEntityMap[$ord])) {
            return '&' . static::$htmlNamedEntityMap[$ord] . ';';
        }

        /**
         * Per OWASP recommendations, we'll use upper hex entities
         * for any other characters where a named entity does not exist.
         */
        if ($ord > 255) {
            return sprintf('&#x%04X;', $ord);
        }
        return sprintf('&#x%02X;', $ord);
    }

    /**
     * Callback function for preg_replace_callback that applies Javascript
     * escaping to all matches.
     *
     * @param array $matches
     * @return string
     */
    protected function jsMatcher($matches)
    {
        $chr = $matches[0];
        if (strlen($chr) == 1) {
            return sprintf('\\x%02X', ord($chr));
        }
        $chr = $this->convertEncoding($chr, 'UTF-16BE', 'UTF-8');
        $hex = strtoupper(bin2hex($chr));
        if (strlen($hex) <= 4) {
            return sprintf('\\u%04s', $hex);
        }
        $highSurrogate = substr($hex, 0, 4);
        $lowSurrogate = substr($hex, 4, 4);
        return sprintf('\\u%04s\\u%04s', $highSurrogate, $lowSurrogate);
    }

    /**
     * Callback function for preg_replace_callback that applies CSS
     * escaping to all matches.
     *
     * @param array $matches
     * @return string
     */
    protected function cssMatcher($matches)
    {
        $chr = $matches[0];
        if (strlen($chr) == 1) {
            $ord = ord($chr);
        } else {
            $chr = $this->convertEncoding($chr, 'UTF-32BE', 'UTF-8');
            $ord = hexdec(bin2hex($chr));
        }
        return sprintf('\\%X ', $ord);
    }

    /**
     * Converts a string to UTF-8 from the base encoding. The base encoding is set via this
     * class' constructor.
     *
     * @param string $string
     * @throws Exception\RuntimeException
     * @return string
     */
    protected function toUtf8($string)
    {
        if ($this->getEncoding() === 'utf-8') {
            $result = $string;
        } else {
            $result = $this->convertEncoding($string, 'UTF-8', $this->getEncoding());
        }

        if (!$this->isUtf8($result)) {
            throw new Exception\RuntimeException(
                sprintf('String to be escaped was not valid UTF-8 or could not be converted: %s', $result)
            );
        }

        return $result;
    }

    /**
     * Converts a string from UTF-8 to the base encoding. The base encoding is set via this
     * class' constructor.
     * @param string $string
     * @return string
     */
    protected function fromUtf8($string)
    {
        if ($this->getEncoding() === 'utf-8') {
            return $string;
        }

        return $this->convertEncoding($string, $this->getEncoding(), 'UTF-8');
    }

    /**
     * Checks if a given string appears to be valid UTF-8 or not.
     *
     * @param string $string
     * @return bool
     */
    protected function isUtf8($string)
    {
        return ($string === '' || preg_match('/^./su', $string));
    }

    /**
     * Encoding conversion helper which wraps iconv and mbstring where they exist or throws
     * and exception where neither is available.
     *
     * @param string $string
     * @param string $to
     * @param array|string $from
     * @throws Exception\RuntimeException
     * @return string
     */
    protected function convertEncoding($string, $to, $from)
    {
        if (function_exists('iconv')) {
            $result = iconv($from, $to, $string);
        } elseif (function_exists('mb_convert_encoding')) {
            $result = mb_convert_encoding($string, $to, $from);
        } else {
            throw new Exception\RuntimeException(
                get_class($this)
                . ' requires either the iconv or mbstring extension to be installed'
                . ' when escaping for non UTF-8 strings.'
            );
        }

        if ($result === false) {
            return ''; // return non-fatal blank string on encoding errors from users
        }
        return $result;
    }
}
