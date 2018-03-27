<?php

namespace Stringy;

class Stringy implements \Countable, \IteratorAggregate, \ArrayAccess
{
    /**
     * An instance's string.
     *
     * @var string
     */
    protected $str;

    /**
     * The string's encoding, which should be one of the mbstring module's
     * supported encodings.
     *
     * @var string
     */
    protected $encoding;

    /**
     * Initializes a Stringy object and assigns both str and encoding properties
     * the supplied values. $str is cast to a string prior to assignment, and if
     * $encoding is not specified, it defaults to mb_internal_encoding(). Throws
     * an InvalidArgumentException if the first argument is an array or object
     * without a __toString method.
     *
     * @param  mixed  $str      Value to modify, after being cast to string
     * @param  string $encoding The character encoding
     * @throws \InvalidArgumentException if an array or object without a
     *         __toString method is passed as the first argument
     */
    public function __construct($str, $encoding = null)
    {
        if (is_array($str)) {
            throw new \InvalidArgumentException(
                'Passed value cannot be an array'
            );
        } elseif (is_object($str) && !method_exists($str, '__toString')) {
            throw new \InvalidArgumentException(
                'Passed object must have a __toString method'
            );
        }

        $this->str = (string) $str;
        $this->encoding = $encoding ?: mb_internal_encoding();
    }

    /**
     * Creates a Stringy object and assigns both str and encoding properties
     * the supplied values. $str is cast to a string prior to assignment, and if
     * $encoding is not specified, it defaults to mb_internal_encoding(). It
     * then returns the initialized object. Throws an InvalidArgumentException
     * if the first argument is an array or object without a __toString method.
     *
     * @param  mixed   $str      Value to modify, after being cast to string
     * @param  string  $encoding The character encoding
     * @return Stringy A Stringy object
     * @throws \InvalidArgumentException if an array or object without a
     *         __toString method is passed as the first argument
     */
    public static function create($str, $encoding = null)
    {
        return new static($str, $encoding);
    }

    /**
     * Returns the value in $str.
     *
     * @return string The current value of the $str property
     */
    public function __toString()
    {
        return $this->str;
    }

    /**
     * Returns the encoding used by the Stringy object.
     *
     * @return string The current value of the $encoding property
     */
    public function getEncoding()
    {
        return $this->encoding;
    }

    /**
     * Returns the length of the string, implementing the countable interface.
     *
     * @return int The number of characters in the string, given the encoding
     */
    public function count()
    {
        return $this->length();
    }

    /**
     * Returns a new ArrayIterator, thus implementing the IteratorAggregate
     * interface. The ArrayIterator's constructor is passed an array of chars
     * in the multibyte string. This enables the use of foreach with instances
     * of Stringy\Stringy.
     *
     * @return \ArrayIterator An iterator for the characters in the string
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->chars());
    }

    /**
     * Returns whether or not a character exists at an index. Offsets may be
     * negative to count from the last character in the string. Implements
     * part of the ArrayAccess interface.
     *
     * @param  mixed   $offset The index to check
     * @return boolean Whether or not the index exists
     */
    public function offsetExists($offset)
    {
        $length = $this->length();
        $offset = (int) $offset;

        if ($offset >= 0) {
            return ($length > $offset);
        }

        return ($length >= abs($offset));
    }

    /**
     * Returns the character at the given index. Offsets may be negative to
     * count from the last character in the string. Implements part of the
     * ArrayAccess interface, and throws an OutOfBoundsException if the index
     * does not exist.
     *
     * @param  mixed $offset         The index from which to retrieve the char
     * @return mixed                 The character at the specified index
     * @throws \OutOfBoundsException If the positive or negative offset does
     *                               not exist
     */
    public function offsetGet($offset)
    {
        $offset = (int) $offset;
        $length = $this->length();

        if (($offset >= 0 && $length <= $offset) || $length < abs($offset)) {
            throw new \OutOfBoundsException('No character exists at the index');
        }

        return mb_substr($this->str, $offset, 1, $this->encoding);
    }

    /**
     * Implements part of the ArrayAccess interface, but throws an exception
     * when called. This maintains the immutability of Stringy objects.
     *
     * @param  mixed      $offset The index of the character
     * @param  mixed      $value  Value to set
     * @throws \Exception When called
     */
    public function offsetSet($offset, $value)
    {
        // Stringy is immutable, cannot directly set char
        throw new \Exception('Stringy object is immutable, cannot modify char');
    }

    /**
     * Implements part of the ArrayAccess interface, but throws an exception
     * when called. This maintains the immutability of Stringy objects.
     *
     * @param  mixed      $offset The index of the character
     * @throws \Exception When called
     */
    public function offsetUnset($offset)
    {
        // Don't allow directly modifying the string
        throw new \Exception('Stringy object is immutable, cannot unset char');
    }

    /**
     * Returns an array consisting of the characters in the string.
     *
     * @return array An array of string chars
     */
    public function chars()
    {
        $chars = array();
        for ($i = 0, $l = $this->length(); $i < $l; $i++) {
            $chars[] = $this->at($i)->str;
        }

        return $chars;
    }

    /**
     * Converts the first character of the supplied string to upper case.
     *
     * @return Stringy Object with the first character of $str being upper case
     */
    public function upperCaseFirst()
    {
        $first = mb_substr($this->str, 0, 1, $this->encoding);
        $rest = mb_substr($this->str, 1, $this->length() - 1,
            $this->encoding);

        $str = mb_strtoupper($first, $this->encoding) . $rest;

        return static::create($str, $this->encoding);
    }

    /**
     * Converts the first character of the string to lower case.
     *
     * @return Stringy Object with the first character of $str being lower case
     */
    public function lowerCaseFirst()
    {
        $first = mb_substr($this->str, 0, 1, $this->encoding);
        $rest = mb_substr($this->str, 1, $this->length() - 1,
            $this->encoding);

        $str = mb_strtolower($first, $this->encoding) . $rest;

        return static::create($str, $this->encoding);
    }

    /**
     * Returns a camelCase version of the string. Trims surrounding spaces,
     * capitalizes letters following digits, spaces, dashes and underscores,
     * and removes spaces, dashes, as well as underscores.
     *
     * @return Stringy Object with $str in camelCase
     */
    public function camelize()
    {
        $encoding = $this->encoding;
        $stringy = $this->trim()->lowerCaseFirst();

        $camelCase = preg_replace_callback(
            '/[-_\s]+(.)?/u',
            function ($match) use ($encoding) {
                return $match[1] ? mb_strtoupper($match[1], $encoding) : '';
            },
            $stringy->str
        );

        $stringy->str = preg_replace_callback(
            '/[\d]+(.)?/u',
            function ($match) use ($encoding) {
                return mb_strtoupper($match[0], $encoding);
            },
            $camelCase
        );

        return $stringy;
    }

    /**
     * Returns an UpperCamelCase version of the supplied string. It trims
     * surrounding spaces, capitalizes letters following digits, spaces, dashes
     * and underscores, and removes spaces, dashes, underscores.
     *
     * @return Stringy Object with $str in UpperCamelCase
     */
    public function upperCamelize()
    {
        return $this->camelize()->upperCaseFirst();
    }

    /**
     * Returns a lowercase and trimmed string separated by dashes. Dashes are
     * inserted before uppercase characters (with the exception of the first
     * character of the string), and in place of spaces as well as underscores.
     *
     * @return Stringy Object with a dasherized $str
     */
    public function dasherize()
    {
        return $this->delimit('-');
    }

    /**
     * Returns a lowercase and trimmed string separated by underscores.
     * Underscores are inserted before uppercase characters (with the exception
     * of the first character of the string), and in place of spaces as well as
     * dashes.
     *
     * @return Stringy Object with an underscored $str
     */
    public function underscored()
    {
        return $this->delimit('_');
    }

    /**
     * Returns a lowercase and trimmed string separated by the given delimiter.
     * Delimiters are inserted before uppercase characters (with the exception
     * of the first character of the string), and in place of spaces, dashes,
     * and underscores. Alpha delimiters are not converted to lowercase.
     *
     * @param  string  $delimiter Sequence used to separate parts of the string
     * @return Stringy Object with a delimited $str
     */
    public function delimit($delimiter)
    {
        // Save current regex encoding so we can reset it after
        $regexEncoding = mb_regex_encoding();
        mb_regex_encoding($this->encoding);

        $str = mb_ereg_replace('\B([A-Z])', '-\1', $this->trim());
        $str = mb_strtolower($str, $this->encoding);
        $str = mb_ereg_replace('[-_\s]+', $delimiter, $str);

        mb_regex_encoding($regexEncoding);

        return static::create($str, $this->encoding);
    }

    /**
     * Returns a case swapped version of the string.
     *
     * @return Stringy Object whose $str has each character's case swapped
     */
    public function swapCase()
    {
        $stringy = static::create($this->str, $this->encoding);
        $encoding = $stringy->encoding;

        $stringy->str = preg_replace_callback(
            '/[\S]/u',
            function ($match) use ($encoding) {
                if ($match[0] == mb_strtoupper($match[0], $encoding)) {
                    return mb_strtolower($match[0], $encoding);
                } else {
                    return mb_strtoupper($match[0], $encoding);
                }
            },
            $stringy->str
        );

        return $stringy;
    }

    /**
     * Returns a trimmed string with the first letter of each word capitalized.
     * Ignores the case of other letters, preserving any acronyms. Also accepts
     * an array, $ignore, allowing you to list words not to be capitalized.
     *
     * @param  array   $ignore An array of words not to capitalize
     * @return Stringy Object with a titleized $str
     */
    public function titleize($ignore = null)
    {
        $buffer = $this->trim();
        $encoding = $this->encoding;

        $buffer = preg_replace_callback(
            '/([\S]+)/u',
            function ($match) use ($encoding, $ignore) {
                if ($ignore && in_array($match[0], $ignore)) {
                    return $match[0];
                } else {
                    $stringy = new Stringy($match[0], $encoding);
                    return (string) $stringy->upperCaseFirst();
                }
            },
            $buffer
        );

        return new Stringy($buffer, $encoding);
    }

    /**
     * Capitalizes the first word of the string, replaces underscores with
     * spaces, and strips '_id'.
     *
     * @return Stringy Object with a humanized $str
     */
    public function humanize()
    {
        $str = str_replace(array('_id', '_'), array('', ' '), $this->str);

        return static::create($str, $this->encoding)->trim()->upperCaseFirst();
    }

    /**
     * Returns a string with smart quotes, ellipsis characters, and dashes from
     * Windows-1252 (commonly used in Word documents) replaced by their ASCII
     * equivalents.
     *
     * @return Stringy Object whose $str has those characters removed
     */
    public function tidy()
    {
        $str = preg_replace(array(
            '/\x{2026}/u',
            '/[\x{201C}\x{201D}]/u',
            '/[\x{2018}\x{2019}]/u',
            '/[\x{2013}\x{2014}]/u',
        ), array(
            '...',
            '"',
            "'",
            '-',
        ), $this->str);

        return static::create($str, $this->encoding);
    }

    /**
     * Trims the string and replaces consecutive whitespace characters with a
     * single space. This includes tabs and newline characters, as well as
     * multibyte whitespace such as the thin space and ideographic space.
     *
     * @return Stringy Object with a trimmed $str and condensed whitespace
     */
    public function collapseWhitespace()
    {
        return $this->regexReplace('[[:space:]]+', ' ')->trim();
    }

    /**
     * Returns an ASCII version of the string. A set of non-ASCII characters are
     * replaced with their closest ASCII counterparts, and the rest are removed
     * unless instructed otherwise.
     *
     * @param  bool    $removeUnsupported Whether or not to remove the
     *                                    unsupported characters
     * @return Stringy Object whose $str contains only ASCII characters
     */
    public function toAscii($removeUnsupported = true)
    {
        $str = $this->str;

        foreach ($this->charsArray() as $key => $value) {
            $str = str_replace($value, $key, $str);
        }

        if ($removeUnsupported) {
            $str = preg_replace('/[^\x20-\x7E]/u', '', $str);
        }

        return static::create($str, $this->encoding);
    }

    /**
     * Returns the replacements for the toAscii() method.
     *
     * @return array An array of replacements.
     */
    protected function charsArray()
    {
        static $charsArray;
        if (isset($charsArray)) return $charsArray;

        return $charsArray = array(
            'a'    => array(
                            'à', 'á', 'ả', 'ã', 'ạ', 'ă', 'ắ', 'ằ', 'ẳ', 'ẵ',
                            'ặ', 'â', 'ấ', 'ầ', 'ẩ', 'ẫ', 'ậ', 'ä', 'ā', 'ą',
                            'å', 'α', 'ά', 'ἀ', 'ἁ', 'ἂ', 'ἃ', 'ἄ', 'ἅ', 'ἆ',
                            'ἇ', 'ᾀ', 'ᾁ', 'ᾂ', 'ᾃ', 'ᾄ', 'ᾅ', 'ᾆ', 'ᾇ', 'ὰ',
                            'ά', 'ᾰ', 'ᾱ', 'ᾲ', 'ᾳ', 'ᾴ', 'ᾶ', 'ᾷ', 'а', 'أ'),
            'b'    => array('б', 'β', 'Ъ', 'Ь', 'ب'),
            'c'    => array('ç', 'ć', 'č', 'ĉ', 'ċ'),
            'd'    => array('ď', 'ð', 'đ', 'ƌ', 'ȡ', 'ɖ', 'ɗ', 'ᵭ', 'ᶁ', 'ᶑ',
                            'д', 'δ', 'د', 'ض'),
            'e'    => array('é', 'è', 'ẻ', 'ẽ', 'ẹ', 'ê', 'ế', 'ề', 'ể', 'ễ',
                            'ệ', 'ë', 'ē', 'ę', 'ě', 'ĕ', 'ė', 'ε', 'έ', 'ἐ',
                            'ἑ', 'ἒ', 'ἓ', 'ἔ', 'ἕ', 'ὲ', 'έ', 'е', 'ё', 'э',
                            'є', 'ə'),
            'f'    => array('ф', 'φ', 'ف'),
            'g'    => array('ĝ', 'ğ', 'ġ', 'ģ', 'г', 'ґ', 'γ', 'ج'),
            'h'    => array('ĥ', 'ħ', 'η', 'ή', 'ح', 'ه'),
            'i'    => array('í', 'ì', 'ỉ', 'ĩ', 'ị', 'î', 'ï', 'ī', 'ĭ', 'į',
                            'ı', 'ι', 'ί', 'ϊ', 'ΐ', 'ἰ', 'ἱ', 'ἲ', 'ἳ', 'ἴ',
                            'ἵ', 'ἶ', 'ἷ', 'ὶ', 'ί', 'ῐ', 'ῑ', 'ῒ', 'ΐ', 'ῖ',
                            'ῗ', 'і', 'ї', 'и'),
            'j'    => array('ĵ', 'ј', 'Ј'),
            'k'    => array('ķ', 'ĸ', 'к', 'κ', 'Ķ', 'ق', 'ك'),
            'l'    => array('ł', 'ľ', 'ĺ', 'ļ', 'ŀ', 'л', 'λ', 'ل'),
            'm'    => array('м', 'μ', 'م'),
            'n'    => array('ñ', 'ń', 'ň', 'ņ', 'ŉ', 'ŋ', 'ν', 'н', 'ن'),
            'o'    => array('ó', 'ò', 'ỏ', 'õ', 'ọ', 'ô', 'ố', 'ồ', 'ổ', 'ỗ',
                            'ộ', 'ơ', 'ớ', 'ờ', 'ở', 'ỡ', 'ợ', 'ø', 'ō', 'ő',
                            'ŏ', 'ο', 'ὀ', 'ὁ', 'ὂ', 'ὃ', 'ὄ', 'ὅ', 'ὸ', 'ό',
                            'ö', 'о', 'و', 'θ'),
            'p'    => array('п', 'π'),
            'r'    => array('ŕ', 'ř', 'ŗ', 'р', 'ρ', 'ر'),
            's'    => array('ś', 'š', 'ş', 'с', 'σ', 'ș', 'ς', 'س', 'ص'),
            't'    => array('ť', 'ţ', 'т', 'τ', 'ț', 'ت', 'ط'),
            'u'    => array('ú', 'ù', 'ủ', 'ũ', 'ụ', 'ư', 'ứ', 'ừ', 'ử', 'ữ',
                            'ự', 'ü', 'û', 'ū', 'ů', 'ű', 'ŭ', 'ų', 'µ', 'у'),
            'v'    => array('в'),
            'w'    => array('ŵ', 'ω', 'ώ'),
            'x'    => array('χ'),
            'y'    => array('ý', 'ỳ', 'ỷ', 'ỹ', 'ỵ', 'ÿ', 'ŷ', 'й', 'ы', 'υ',
                            'ϋ', 'ύ', 'ΰ', 'ي'),
            'z'    => array('ź', 'ž', 'ż', 'з', 'ζ', 'ز'),
            'aa'   => array('ع'),
            'ae'   => array('æ'),
            'ch'   => array('ч'),
            'dj'   => array('ђ', 'đ'),
            'dz'   => array('џ'),
            'gh'   => array('غ'),
            'kh'   => array('х', 'خ'),
            'lj'   => array('љ'),
            'nj'   => array('њ'),
            'oe'   => array('œ'),
            'ps'   => array('ψ'),
            'sh'   => array('ш'),
            'shch' => array('щ'),
            'ss'   => array('ß'),
            'th'   => array('þ', 'ث', 'ذ', 'ظ'),
            'ts'   => array('ц'),
            'ya'   => array('я'),
            'yu'   => array('ю'),
            'zh'   => array('ж'),
            '(c)'  => array('©'),
            'A'    => array('Á', 'À', 'Ả', 'Ã', 'Ạ', 'Ă', 'Ắ', 'Ằ', 'Ẳ', 'Ẵ',
                            'Ặ', 'Â', 'Ấ', 'Ầ', 'Ẩ', 'Ẫ', 'Ậ', 'Ä', 'Å', 'Ā',
                            'Ą', 'Α', 'Ά', 'Ἀ', 'Ἁ', 'Ἂ', 'Ἃ', 'Ἄ', 'Ἅ', 'Ἆ',
                            'Ἇ', 'ᾈ', 'ᾉ', 'ᾊ', 'ᾋ', 'ᾌ', 'ᾍ', 'ᾎ', 'ᾏ', 'Ᾰ',
                            'Ᾱ', 'Ὰ', 'Ά', 'ᾼ', 'А'),
            'B'    => array('Б', 'Β'),
            'C'    => array('Ç','Ć', 'Č', 'Ĉ', 'Ċ'),
            'D'    => array('Ď', 'Ð', 'Đ', 'Ɖ', 'Ɗ', 'Ƌ', 'ᴅ', 'ᴆ', 'Д', 'Δ'),
            'E'    => array('É', 'È', 'Ẻ', 'Ẽ', 'Ẹ', 'Ê', 'Ế', 'Ề', 'Ể', 'Ễ',
                            'Ệ', 'Ë', 'Ē', 'Ę', 'Ě', 'Ĕ', 'Ė', 'Ε', 'Έ', 'Ἐ',
                            'Ἑ', 'Ἒ', 'Ἓ', 'Ἔ', 'Ἕ', 'Έ', 'Ὲ', 'Е', 'Ё', 'Э',
                            'Є', 'Ə'),
            'F'    => array('Ф', 'Φ'),
            'G'    => array('Ğ', 'Ġ', 'Ģ', 'Г', 'Ґ', 'Γ'),
            'H'    => array('Η', 'Ή'),
            'I'    => array('Í', 'Ì', 'Ỉ', 'Ĩ', 'Ị', 'Î', 'Ï', 'Ī', 'Ĭ', 'Į',
                            'İ', 'Ι', 'Ί', 'Ϊ', 'Ἰ', 'Ἱ', 'Ἳ', 'Ἴ', 'Ἵ', 'Ἶ',
                            'Ἷ', 'Ῐ', 'Ῑ', 'Ὶ', 'Ί', 'И', 'І', 'Ї'),
            'K'    => array('К', 'Κ'),
            'L'    => array('Ĺ', 'Ł', 'Л', 'Λ', 'Ļ'),
            'M'    => array('М', 'Μ'),
            'N'    => array('Ń', 'Ñ', 'Ň', 'Ņ', 'Ŋ', 'Н', 'Ν'),
            'O'    => array('Ó', 'Ò', 'Ỏ', 'Õ', 'Ọ', 'Ô', 'Ố', 'Ồ', 'Ổ', 'Ỗ',
                            'Ộ', 'Ơ', 'Ớ', 'Ờ', 'Ở', 'Ỡ', 'Ợ', 'Ö', 'Ø', 'Ō',
                            'Ő', 'Ŏ', 'Ο', 'Ό', 'Ὀ', 'Ὁ', 'Ὂ', 'Ὃ', 'Ὄ', 'Ὅ',
                            'Ὸ', 'Ό', 'О', 'Θ', 'Ө'),
            'P'    => array('П', 'Π'),
            'R'    => array('Ř', 'Ŕ', 'Р', 'Ρ'),
            'S'    => array('Ş', 'Ŝ', 'Ș', 'Š', 'Ś', 'С', 'Σ'),
            'T'    => array('Ť', 'Ţ', 'Ŧ', 'Ț', 'Т', 'Τ'),
            'U'    => array('Ú', 'Ù', 'Ủ', 'Ũ', 'Ụ', 'Ư', 'Ứ', 'Ừ', 'Ử', 'Ữ',
                            'Ự', 'Û', 'Ü', 'Ū', 'Ů', 'Ű', 'Ŭ', 'Ų', 'У'),
            'V'    => array('В'),
            'W'    => array('Ω', 'Ώ'),
            'X'    => array('Χ'),
            'Y'    => array('Ý', 'Ỳ', 'Ỷ', 'Ỹ', 'Ỵ', 'Ÿ', 'Ῠ', 'Ῡ', 'Ὺ', 'Ύ',
                            'Ы', 'Й', 'Υ', 'Ϋ'),
            'Z'    => array('Ź', 'Ž', 'Ż', 'З', 'Ζ'),
            'AE'   => array('Æ'),
            'CH'   => array('Ч'),
            'DJ'   => array('Ђ'),
            'DZ'   => array('Џ'),
            'KH'   => array('Х'),
            'LJ'   => array('Љ'),
            'NJ'   => array('Њ'),
            'PS'   => array('Ψ'),
            'SH'   => array('Ш'),
            'SHCH' => array('Щ'),
            'SS'   => array('ẞ'),
            'TH'   => array('Þ'),
            'TS'   => array('Ц'),
            'YA'   => array('Я'),
            'YU'   => array('Ю'),
            'ZH'   => array('Ж'),
            ' '    => array("\xC2\xA0", "\xE2\x80\x80", "\xE2\x80\x81",
                            "\xE2\x80\x82", "\xE2\x80\x83", "\xE2\x80\x84",
                            "\xE2\x80\x85", "\xE2\x80\x86", "\xE2\x80\x87",
                            "\xE2\x80\x88", "\xE2\x80\x89", "\xE2\x80\x8A",
                            "\xE2\x80\xAF", "\xE2\x81\x9F", "\xE3\x80\x80"),
        );
    }

    /**
     * Pads the string to a given length with $padStr. If length is less than
     * or equal to the length of the string, no padding takes places. The
     * default string used for padding is a space, and the default type (one of
     * 'left', 'right', 'both') is 'right'. Throws an InvalidArgumentException
     * if $padType isn't one of those 3 values.
     *
     * @param  int     $length  Desired string length after padding
     * @param  string  $padStr  String used to pad, defaults to space
     * @param  string  $padType One of 'left', 'right', 'both'
     * @return Stringy Object with a padded $str
     * @throws InvalidArgumentException If $padType isn't one of 'right',
     *         'left' or 'both'
     */
    public function pad($length, $padStr = ' ', $padType = 'right')
    {
        if (!in_array($padType, array('left', 'right', 'both'))) {
            throw new \InvalidArgumentException('Pad expects $padType ' .
                "to be one of 'left', 'right' or 'both'");
        }

        switch ($padType) {
            case 'left':
                return $this->padLeft($length, $padStr);
            case 'right':
                return $this->padRight($length, $padStr);
            default:
                return $this->padBoth($length, $padStr);
        }
    }

    /**
     * Returns a new string of a given length such that the beginning of the
     * string is padded. Alias for pad() with a $padType of 'left'.
     *
     * @param  int     $length Desired string length after padding
     * @param  string  $padStr String used to pad, defaults to space
     * @return Stringy String with left padding
     */
    public function padLeft($length, $padStr = ' ')
    {
        return $this->applyPadding($length - $this->length(), 0, $padStr);
    }

    /**
     * Returns a new string of a given length such that the end of the string
     * is padded. Alias for pad() with a $padType of 'right'.
     *
     * @param  int     $length Desired string length after padding
     * @param  string  $padStr String used to pad, defaults to space
     * @return Stringy String with right padding
     */
    public function padRight($length, $padStr = ' ')
    {
        return $this->applyPadding(0, $length - $this->length(), $padStr);
    }

    /**
     * Returns a new string of a given length such that both sides of the
     * string are padded. Alias for pad() with a $padType of 'both'.
     *
     * @param  int     $length Desired string length after padding
     * @param  string  $padStr String used to pad, defaults to space
     * @return Stringy String with padding applied
     */
    public function padBoth($length, $padStr = ' ')
    {
        $padding = $length - $this->length();

        return $this->applyPadding(floor($padding / 2), ceil($padding / 2),
            $padStr);
    }

    /**
     * Adds the specified amount of left and right padding to the given string.
     * The default character used is a space.
     *
     * @param  int     $left   Length of left padding
     * @param  int     $right  Length of right padding
     * @param  string  $padStr String used to pad
     * @return Stringy String with padding applied
     */
    private function applyPadding($left = 0, $right = 0, $padStr = ' ')
    {
        $stringy = static::create($this->str, $this->encoding);
        $length = mb_strlen($padStr, $stringy->encoding);

        $strLength = $stringy->length();
        $paddedLength = $strLength + $left + $right;

        if (!$length || $paddedLength <= $strLength) {
            return $stringy;
        }

        $leftPadding = mb_substr(str_repeat($padStr, ceil($left / $length)), 0,
            $left, $stringy->encoding);
        $rightPadding = mb_substr(str_repeat($padStr, ceil($right / $length)),
            0, $right, $stringy->encoding);

        $stringy->str = $leftPadding . $stringy->str . $rightPadding;

        return $stringy;
    }

    /**
     * Returns true if the string begins with $substring, false otherwise. By
     * default, the comparison is case-sensitive, but can be made insensitive
     * by setting $caseSensitive to false.
     *
     * @param  string $substring     The substring to look for
     * @param  bool   $caseSensitive Whether or not to enforce case-sensitivity
     * @return bool   Whether or not $str starts with $substring
     */
    public function startsWith($substring, $caseSensitive = true)
    {
        $substringLength = mb_strlen($substring, $this->encoding);
        $startOfStr = mb_substr($this->str, 0, $substringLength,
            $this->encoding);

        if (!$caseSensitive) {
            $substring = mb_strtolower($substring, $this->encoding);
            $startOfStr = mb_strtolower($startOfStr, $this->encoding);
        }

        return (string) $substring === $startOfStr;
    }

    /**
     * Returns true if the string ends with $substring, false otherwise. By
     * default, the comparison is case-sensitive, but can be made insensitive
     * by setting $caseSensitive to false.
     *
     * @param  string $substring     The substring to look for
     * @param  bool   $caseSensitive Whether or not to enforce case-sensitivity
     * @return bool   Whether or not $str ends with $substring
     */
    public function endsWith($substring, $caseSensitive = true)
    {
        $substringLength = mb_strlen($substring, $this->encoding);
        $strLength = $this->length();

        $endOfStr = mb_substr($this->str, $strLength - $substringLength,
            $substringLength, $this->encoding);

        if (!$caseSensitive) {
            $substring = mb_strtolower($substring, $this->encoding);
            $endOfStr = mb_strtolower($endOfStr, $this->encoding);
        }

        return (string) $substring === $endOfStr;
    }

    /**
     * Converts each tab in the string to some number of spaces, as defined by
     * $tabLength. By default, each tab is converted to 4 consecutive spaces.
     *
     * @param  int     $tabLength Number of spaces to replace each tab with
     * @return Stringy Object whose $str has had tabs switched to spaces
     */
    public function toSpaces($tabLength = 4)
    {
        $spaces = str_repeat(' ', $tabLength);
        $str = str_replace("\t", $spaces, $this->str);

        return static::create($str, $this->encoding);
    }

    /**
     * Converts each occurrence of some consecutive number of spaces, as
     * defined by $tabLength, to a tab. By default, each 4 consecutive spaces
     * are converted to a tab.
     *
     * @param  int     $tabLength Number of spaces to replace with a tab
     * @return Stringy Object whose $str has had spaces switched to tabs
     */
    public function toTabs($tabLength = 4)
    {
        $spaces = str_repeat(' ', $tabLength);
        $str = str_replace($spaces, "\t", $this->str);

        return static::create($str, $this->encoding);
    }

    /**
     * Converts the first character of each word in the string to uppercase.
     *
     * @return Stringy Object with all characters of $str being title-cased
     */
    public function toTitleCase()
    {
        $str = mb_convert_case($this->str, MB_CASE_TITLE, $this->encoding);

        return static::create($str, $this->encoding);
    }

    /**
     * Converts all characters in the string to lowercase. An alias for PHP's
     * mb_strtolower().
     *
     * @return Stringy Object with all characters of $str being lowercase
     */
    public function toLowerCase()
    {
        $str = mb_strtolower($this->str, $this->encoding);

        return static::create($str, $this->encoding);
    }

    /**
     * Converts all characters in the string to uppercase. An alias for PHP's
     * mb_strtoupper().
     *
     * @return Stringy Object with all characters of $str being uppercase
     */
    public function toUpperCase()
    {
        $str = mb_strtoupper($this->str, $this->encoding);

        return static::create($str, $this->encoding);
    }

    /**
     * Converts the string into an URL slug. This includes replacing non-ASCII
     * characters with their closest ASCII equivalents, removing remaining
     * non-ASCII and non-alphanumeric characters, and replacing whitespace with
     * $replacement. The replacement defaults to a single dash, and the string
     * is also converted to lowercase.
     *
     * @param  string  $replacement The string used to replace whitespace
     * @return Stringy Object whose $str has been converted to an URL slug
     */
    public function slugify($replacement = '-')
    {
        $stringy = $this->toAscii();

        $quotedReplacement = preg_quote($replacement);
        $pattern = "/[^a-zA-Z\d\s-_$quotedReplacement]/u";
        $stringy->str = preg_replace($pattern, '', $stringy);

        return $stringy->toLowerCase()->delimit($replacement)
                       ->removeLeft($replacement)->removeRight($replacement);
    }

    /**
     * Returns true if the string contains $needle, false otherwise. By default
     * the comparison is case-sensitive, but can be made insensitive by setting
     * $caseSensitive to false.
     *
     * @param  string $needle        Substring to look for
     * @param  bool   $caseSensitive Whether or not to enforce case-sensitivity
     * @return bool   Whether or not $str contains $needle
     */
    public function contains($needle, $caseSensitive = true)
    {
        $encoding = $this->encoding;

        if ($caseSensitive) {
            return (mb_strpos($this->str, $needle, 0, $encoding) !== false);
        } else {
            return (mb_stripos($this->str, $needle, 0, $encoding) !== false);
        }
    }

    /**
     * Returns true if the string contains any $needles, false otherwise. By
     * default the comparison is case-sensitive, but can be made insensitive by
     * setting $caseSensitive to false.
     *
     * @param  array  $needles       Substrings to look for
     * @param  bool   $caseSensitive Whether or not to enforce case-sensitivity
     * @return bool   Whether or not $str contains $needle
     */
    public function containsAny($needles, $caseSensitive = true)
    {
        if (empty($needles)) {
            return false;
        }

        foreach ($needles as $needle) {
            if ($this->contains($needle, $caseSensitive)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns true if the string contains all $needles, false otherwise. By
     * default the comparison is case-sensitive, but can be made insensitive by
     * setting $caseSensitive to false.
     *
     * @param  array  $needles       Substrings to look for
     * @param  bool   $caseSensitive Whether or not to enforce case-sensitivity
     * @return bool   Whether or not $str contains $needle
     */
    public function containsAll($needles, $caseSensitive = true)
    {
        if (empty($needles)) {
            return false;
        }

        foreach ($needles as $needle) {
            if (!$this->contains($needle, $caseSensitive)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Returns the index of the first occurrence of $needle in the string,
     * and false if not found. Accepts an optional offset from which to begin
     * the search.
     *
     * @param  string   $needle Substring to look for
     * @param  int      $offset Offset from which to search
     * @return int|bool The occurrence's index if found, otherwise false
     */
    public function indexOf($needle, $offset = 0)
    {
        return mb_strpos($this->str, (string) $needle,
            (int) $offset, $this->encoding);
    }

    /**
     * Returns the index of the last occurrence of $needle in the string,
     * and false if not found. Accepts an optional offset from which to begin
     * the search.
     *
     * @param  string   $needle Substring to look for
     * @param  int      $offset Offset from which to search
     * @return int|bool The last occurrence's index if found, otherwise false
     */
    public function indexOfLast($needle, $offset = 0)
    {
        return mb_strrpos($this->str, (string) $needle,
            (int) $offset, $this->encoding);
    }

    /**
     * Surrounds $str with the given substring.
     *
     * @param  string  $substring The substring to add to both sides
     * @return Stringy Object whose $str had the substring both prepended and
     *                 appended
     */
    public function surround($substring)
    {
        $str = implode('', array($substring, $this->str, $substring));

        return static::create($str, $this->encoding);
    }

    /**
     * Inserts $substring into the string at the $index provided.
     *
     * @param  string  $substring String to be inserted
     * @param  int     $index     The index at which to insert the substring
     * @return Stringy Object with the resulting $str after the insertion
     */
    public function insert($substring, $index)
    {
        $stringy = static::create($this->str, $this->encoding);
        if ($index > $stringy->length()) {
            return $stringy;
        }

        $start = mb_substr($stringy->str, 0, $index, $stringy->encoding);
        $end = mb_substr($stringy->str, $index, $stringy->length(),
            $stringy->encoding);

        $stringy->str = $start . $substring . $end;

        return $stringy;
    }

    /**
     * Truncates the string to a given length. If $substring is provided, and
     * truncating occurs, the string is further truncated so that the substring
     * may be appended without exceeding the desired length.
     *
     * @param  int     $length    Desired length of the truncated string
     * @param  string  $substring The substring to append if it can fit
     * @return Stringy Object with the resulting $str after truncating
     */
    public function truncate($length, $substring = '')
    {
        $stringy = static::create($this->str, $this->encoding);
        if ($length >= $stringy->length()) {
            return $stringy;
        }

        // Need to further trim the string so we can append the substring
        $substringLength = mb_strlen($substring, $stringy->encoding);
        $length = $length - $substringLength;

        $truncated = mb_substr($stringy->str, 0, $length, $stringy->encoding);
        $stringy->str = $truncated . $substring;

        return $stringy;
    }

    /**
     * Truncates the string to a given length, while ensuring that it does not
     * split words. If $substring is provided, and truncating occurs, the
     * string is further truncated so that the substring may be appended without
     * exceeding the desired length.
     *
     * @param  int     $length    Desired length of the truncated string
     * @param  string  $substring The substring to append if it can fit
     * @return Stringy Object with the resulting $str after truncating
     */
    public function safeTruncate($length, $substring = '')
    {
        $stringy = static::create($this->str, $this->encoding);
        if ($length >= $stringy->length()) {
            return $stringy;
        }

        // Need to further trim the string so we can append the substring
        $encoding = $stringy->encoding;
        $substringLength = mb_strlen($substring, $encoding);
        $length = $length - $substringLength;

        $truncated = mb_substr($stringy->str, 0, $length, $encoding);

        // If the last word was truncated
        if (mb_strpos($stringy->str, ' ', $length - 1, $encoding) != $length) {
            // Find pos of the last occurrence of a space, get up to that
            $lastPos = mb_strrpos($truncated, ' ', 0, $encoding);
            $truncated = mb_substr($truncated, 0, $lastPos, $encoding);
        }

        $stringy->str = $truncated . $substring;

        return $stringy;
    }

    /**
     * Returns a reversed string. A multibyte version of strrev().
     *
     * @return Stringy Object with a reversed $str
     */
    public function reverse()
    {
        $strLength = $this->length();
        $reversed = '';

        // Loop from last index of string to first
        for ($i = $strLength - 1; $i >= 0; $i--) {
            $reversed .= mb_substr($this->str, $i, 1, $this->encoding);
        }

        return static::create($reversed, $this->encoding);
    }

    /**
     * A multibyte str_shuffle() function. It returns a string with its
     * characters in random order.
     *
     * @return Stringy Object with a shuffled $str
     */
    public function shuffle()
    {
        $indexes = range(0, $this->length() - 1);
        shuffle($indexes);

        $shuffledStr = '';
        foreach ($indexes as $i) {
            $shuffledStr .= mb_substr($this->str, $i, 1, $this->encoding);
        }

        return static::create($shuffledStr, $this->encoding);
    }

    /**
     * Returns a string with whitespace removed from the start and end of the
     * string. Supports the removal of unicode whitespace. Accepts an optional
     * string of characters to strip instead of the defaults.
     *
     * @param  string  $chars Optional string of characters to strip
     * @return Stringy Object with a trimmed $str
     */
    public function trim($chars = null)
    {
        $chars = ($chars) ? preg_quote($chars) : '[:space:]';

        return $this->regexReplace("^[$chars]+|[$chars]+\$", '');
    }

    /**
     * Returns a string with whitespace removed from the start of the string.
     * Supports the removal of unicode whitespace. Accepts an optional
     * string of characters to strip instead of the defaults.
     *
     * @param  string  $chars Optional string of characters to strip
     * @return Stringy Object with a trimmed $str
     */
    public function trimLeft($chars = null)
    {
        $chars = ($chars) ? preg_quote($chars) : '[:space:]';

        return $this->regexReplace("^[$chars]+", '');
    }

    /**
     * Returns a string with whitespace removed from the end of the string.
     * Supports the removal of unicode whitespace. Accepts an optional
     * string of characters to strip instead of the defaults.
     *
     * @param  string  $chars Optional string of characters to strip
     * @return Stringy Object with a trimmed $str
     */
    public function trimRight($chars = null)
    {
        $chars = ($chars) ? preg_quote($chars) : '[:space:]';

        return $this->regexReplace("[$chars]+\$", '');
    }

    /**
     * Returns the longest common prefix between the string and $otherStr.
     *
     * @param  string  $otherStr Second string for comparison
     * @return Stringy Object with its $str being the longest common prefix
     */
    public function longestCommonPrefix($otherStr)
    {
        $encoding = $this->encoding;
        $maxLength = min($this->length(), mb_strlen($otherStr, $encoding));

        $longestCommonPrefix = '';
        for ($i = 0; $i < $maxLength; $i++) {
            $char = mb_substr($this->str, $i, 1, $encoding);

            if ($char == mb_substr($otherStr, $i, 1, $encoding)) {
                $longestCommonPrefix .= $char;
            } else {
                break;
            }
        }

        return static::create($longestCommonPrefix, $encoding);
    }

    /**
     * Returns the longest common suffix between the string and $otherStr.
     *
     * @param  string  $otherStr Second string for comparison
     * @return Stringy Object with its $str being the longest common suffix
     */
    public function longestCommonSuffix($otherStr)
    {
        $encoding = $this->encoding;
        $maxLength = min($this->length(), mb_strlen($otherStr, $encoding));

        $longestCommonSuffix = '';
        for ($i = 1; $i <= $maxLength; $i++) {
            $char = mb_substr($this->str, -$i, 1, $encoding);

            if ($char == mb_substr($otherStr, -$i, 1, $encoding)) {
                $longestCommonSuffix = $char . $longestCommonSuffix;
            } else {
                break;
            }
        }

        return static::create($longestCommonSuffix, $encoding);
    }

    /**
     * Returns the longest common substring between the string and $otherStr.
     * In the case of ties, it returns that which occurs first.
     *
     * @param  string  $otherStr Second string for comparison
     * @return Stringy Object with its $str being the longest common substring
     */
    public function longestCommonSubstring($otherStr)
    {
        // Uses dynamic programming to solve
        // http://en.wikipedia.org/wiki/Longest_common_substring_problem
        $encoding = $this->encoding;
        $stringy = static::create($this->str, $encoding);
        $strLength = $stringy->length();
        $otherLength = mb_strlen($otherStr, $encoding);

        // Return if either string is empty
        if ($strLength == 0 || $otherLength == 0) {
            $stringy->str = '';
            return $stringy;
        }

        $len = 0;
        $end = 0;
        $table = array_fill(0, $strLength + 1,
            array_fill(0, $otherLength + 1, 0));

        for ($i = 1; $i <= $strLength; $i++) {
            for ($j = 1; $j <= $otherLength; $j++) {
                $strChar = mb_substr($stringy->str, $i - 1, 1, $encoding);
                $otherChar = mb_substr($otherStr, $j - 1, 1, $encoding);

                if ($strChar == $otherChar) {
                    $table[$i][$j] = $table[$i - 1][$j - 1] + 1;
                    if ($table[$i][$j] > $len) {
                        $len = $table[$i][$j];
                        $end = $i;
                    }
                } else {
                    $table[$i][$j] = 0;
                }
            }
        }

        $stringy->str = mb_substr($stringy->str, $end - $len, $len, $encoding);

        return $stringy;
    }

    /**
     * Returns the length of the string. An alias for PHP's mb_strlen() function.
     *
     * @return int The number of characters in $str given the encoding
     */
    public function length()
    {
        return mb_strlen($this->str, $this->encoding);
    }

    /**
     * Returns the substring beginning at $start with the specified $length.
     * It differs from the mb_substr() function in that providing a $length of
     * null will return the rest of the string, rather than an empty string.
     *
     * @param  int     $start  Position of the first character to use
     * @param  int     $length Maximum number of characters used
     * @return Stringy Object with its $str being the substring
     */
    public function substr($start, $length = null)
    {
        $length = $length === null ? $this->length() : $length;
        $str = mb_substr($this->str, $start, $length, $this->encoding);

        return static::create($str, $this->encoding);
    }

    /**
     * Returns the character at $index, with indexes starting at 0.
     *
     * @param  int     $index Position of the character
     * @return Stringy The character at $index
     */
    public function at($index)
    {
        return $this->substr($index, 1);
    }

    /**
     * Returns the first $n characters of the string.
     *
     * @param  int     $n Number of characters to retrieve from the start
     * @return Stringy Object with its $str being the first $n chars
     */
    public function first($n)
    {
        $stringy = static::create($this->str, $this->encoding);

        if ($n < 0) {
            $stringy->str = '';
        } else {
            return $stringy->substr(0, $n);
        }

        return $stringy;
    }

    /**
     * Returns the last $n characters of the string.
     *
     * @param  int     $n Number of characters to retrieve from the end
     * @return Stringy Object with its $str being the last $n chars
     */
    public function last($n)
    {
        $stringy = static::create($this->str, $this->encoding);

        if ($n <= 0) {
            $stringy->str = '';
        } else {
            return $stringy->substr(-$n);
        }

        return $stringy;
    }

    /**
     * Ensures that the string begins with $substring. If it doesn't, it's
     * prepended.
     *
     * @param  string  $substring The substring to add if not present
     * @return Stringy Object with its $str prefixed by the $substring
     */
    public function ensureLeft($substring)
    {
        $stringy = static::create($this->str, $this->encoding);

        if (!$stringy->startsWith($substring)) {
            $stringy->str = $substring . $stringy->str;
        }

        return $stringy;
    }

    /**
     * Ensures that the string begins with $substring. If it doesn't, it's
     * appended.
     *
     * @param  string  $substring The substring to add if not present
     * @return Stringy Object with its $str suffixed by the $substring
     */
    public function ensureRight($substring)
    {
        $stringy = static::create($this->str, $this->encoding);

        if (!$stringy->endsWith($substring)) {
            $stringy->str .= $substring;
        }

        return $stringy;
    }

    /**
     * Returns a new string with the prefix $substring removed, if present.
     *
     * @param  string  $substring The prefix to remove
     * @return Stringy Object having a $str without the prefix $substring
     */
    public function removeLeft($substring)
    {
        $stringy = static::create($this->str, $this->encoding);

        if ($stringy->startsWith($substring)) {
            $substringLength = mb_strlen($substring, $stringy->encoding);
            return $stringy->substr($substringLength);
        }

        return $stringy;
    }

    /**
     * Returns a new string with the suffix $substring removed, if present.
     *
     * @param  string  $substring The suffix to remove
     * @return Stringy Object having a $str without the suffix $substring
     */
    public function removeRight($substring)
    {
        $stringy = static::create($this->str, $this->encoding);

        if ($stringy->endsWith($substring)) {
            $substringLength = mb_strlen($substring, $stringy->encoding);
            return $stringy->substr(0, $stringy->length() - $substringLength);
        }

        return $stringy;
    }

    /**
     * Returns true if $str matches the supplied pattern, false otherwise.
     *
     * @param  string $pattern Regex pattern to match against
     * @return bool   Whether or not $str matches the pattern
     */
    private function matchesPattern($pattern)
    {
        $regexEncoding = mb_regex_encoding();
        mb_regex_encoding($this->encoding);

        $match = mb_ereg_match($pattern, $this->str);
        mb_regex_encoding($regexEncoding);

        return $match;
    }

    /**
     * Returns true if the string contains a lower case char, false
     * otherwise.
     *
     * @return bool Whether or not the string contains a lower case character.
     */
    public function hasLowerCase()
    {
        return $this->matchesPattern('.*[[:lower:]]');
    }

    /**
     * Returns true if the string contains an upper case char, false
     * otherwise.
     *
     * @return bool Whether or not the string contains an upper case character.
     */
    public function hasUpperCase()
    {
        return $this->matchesPattern('.*[[:upper:]]');
    }

    /**
     * Returns true if the string contains only alphabetic chars, false
     * otherwise.
     *
     * @return bool Whether or not $str contains only alphabetic chars
     */
    public function isAlpha()
    {
        return $this->matchesPattern('^[[:alpha:]]*$');
    }

    /**
     * Returns true if the string contains only alphabetic and numeric chars,
     * false otherwise.
     *
     * @return bool Whether or not $str contains only alphanumeric chars
     */
    public function isAlphanumeric()
    {
        return $this->matchesPattern('^[[:alnum:]]*$');
    }

    /**
     * Returns true if the string contains only hexadecimal chars, false
     * otherwise.
     *
     * @return bool Whether or not $str contains only hexadecimal chars
     */
    public function isHexadecimal()
    {
        return $this->matchesPattern('^[[:xdigit:]]*$');
    }

    /**
     * Returns true if the string contains only whitespace chars, false
     * otherwise.
     *
     * @return bool Whether or not $str contains only whitespace characters
     */
    public function isBlank()
    {
        return $this->matchesPattern('^[[:space:]]*$');
    }

    /**
     * Returns true if the string is JSON, false otherwise.
     *
     * @return bool Whether or not $str is JSON
     */
    public function isJson()
    {
        json_decode($this->str);

        return (json_last_error() === JSON_ERROR_NONE);
    }

    /**
     * Returns true if the string contains only lower case chars, false
     * otherwise.
     *
     * @return bool Whether or not $str contains only lower case characters
     */
    public function isLowerCase()
    {
        return $this->matchesPattern('^[[:lower:]]*$');
    }

    /**
     * Returns true if the string contains only lower case chars, false
     * otherwise.
     *
     * @return bool Whether or not $str contains only lower case characters
     */
    public function isUpperCase()
    {
        return $this->matchesPattern('^[[:upper:]]*$');
    }

    /**
     * Returns true if the string is serialized, false otherwise.
     *
     * @return bool Whether or not $str is serialized
     */
    public function isSerialized()
    {
        return $this->str === 'b:0;' || @unserialize($this->str) !== false;
    }

    /**
     * Returns the number of occurrences of $substring in the given string.
     * By default, the comparison is case-sensitive, but can be made insensitive
     * by setting $caseSensitive to false.
     *
     * @param  string $substring     The substring to search for
     * @param  bool   $caseSensitive Whether or not to enforce case-sensitivity
     * @return int    The number of $substring occurrences
     */
    public function countSubstr($substring, $caseSensitive = true)
    {
        if ($caseSensitive) {
            return mb_substr_count($this->str, $substring, $this->encoding);
        }

        $str = mb_strtoupper($this->str, $this->encoding);
        $substring = mb_strtoupper($substring, $this->encoding);

        return mb_substr_count($str, $substring, $this->encoding);
    }

    /**
     * Replaces all occurrences of $search in $str by $replacement.
     *
     * @param  string  $search      The needle to search for
     * @param  string  $replacement The string to replace with
     * @return Stringy Object with the resulting $str after the replacements
     */
    public function replace($search, $replacement)
    {
        return $this->regexReplace(preg_quote($search), $replacement);
    }

    /**
     * Replaces all occurrences of $pattern in $str by $replacement. An alias
     * for mb_ereg_replace(). Note that the 'i' option with multibyte patterns
     * in mb_ereg_replace() requires PHP 5.4+. This is due to a lack of support
     * in the bundled version of Oniguruma in PHP 5.3.
     *
     * @param  string  $pattern     The regular expression pattern
     * @param  string  $replacement The string to replace with
     * @param  string  $options     Matching conditions to be used
     * @return Stringy Object with the resulting $str after the replacements
     */
    public function regexReplace($pattern, $replacement, $options = 'msr')
    {
        $regexEncoding = mb_regex_encoding();
        mb_regex_encoding($this->encoding);

        $str = mb_ereg_replace($pattern, $replacement, $this->str, $options);
        mb_regex_encoding($regexEncoding);

        return static::create($str, $this->encoding);
    }

    /**
     * Convert all applicable characters to HTML entities.
     *
     * @param  int|null $flags See http://php.net/manual/en/function.htmlentities.php
     * @return Stringy  Object with the resulting $str after being html encoded.
     */
    public function htmlEncode($flags = ENT_COMPAT)
    {
        $str = htmlentities($this->str, $flags, $this->encoding);

        return static::create($str, $this->encoding);
    }

    /**
     * Convert all HTML entities to their applicable characters.
     *
     * @param  int|null $flags See http://php.net/manual/en/function.html-entity-decode.php
     * @return Stringy  Object with the resulting $str after being html decoded.
     */
    public function htmlDecode($flags = ENT_COMPAT)
    {
        $str = html_entity_decode($this->str, $flags, $this->encoding);

        return static::create($str, $this->encoding);
    }
}
