<?php

/*
 * The RandomLib library for securely generating random numbers and strings in PHP
 *
 * @author     Anthony Ferrara <ircmaxell@ircmaxell.com>
 * @copyright  2011 The Authors
 * @license    http://www.opensource.org/licenses/mit-license.html  MIT License
 * @version    Build @@version@@
 */

/**
 * The Random Number Generator Class
 *
 * Use this factory to generate cryptographic quality random numbers (strings)
 *
 * PHP version 5.3
 *
 * @category   PHPPasswordLib
 * @package    Random
 *
 * @author     Anthony Ferrara <ircmaxell@ircmaxell.com>
 * @author     Timo Hamina
 * @copyright  2011 The Authors
 * @license    http://www.opensource.org/licenses/mit-license.html  MIT License
 *
 * @version    Build @@version@@
 */
namespace RandomLib;

/**
 * The Random Number Generator Class
 *
 * Use this factory to generate cryptographic quality random numbers (strings)
 *
 * @category   PHPPasswordLib
 * @package    Random
 *
 * @author     Anthony Ferrara <ircmaxell@ircmaxell.com>
 * @author     Timo Hamina
 */
class Generator
{

    /**
     * @const Flag for uppercase letters
     */
    const CHAR_UPPER = 1;

    /**
     * @const Flag for lowercase letters
     */
    const CHAR_LOWER = 2;

    /**
     * @const Flag for alpha characters (combines UPPER + LOWER)
     */
    const CHAR_ALPHA = 3; // CHAR_UPPER | CHAR_LOWER

    /**
     * @const Flag for digits
     */
    const CHAR_DIGITS = 4;

    /**
     * @const Flag for alpha numeric characters
     */
    const CHAR_ALNUM = 7; // CHAR_ALPHA | CHAR_DIGITS

    /**
     * @const Flag for uppercase hexadecimal symbols
     */
    const CHAR_UPPER_HEX = 12; // 8 | CHAR_DIGITS

    /**
     * @const Flag for lowercase hexidecimal symbols
     */
    const CHAR_LOWER_HEX = 20; // 16 | CHAR_DIGITS

    /**
     * @const Flag for base64 symbols
     */
    const CHAR_BASE64 = 39; // 32 | CHAR_ALNUM

    /**
     * @const Flag for additional symbols accessible via the keyboard
     */
    const CHAR_SYMBOLS = 64;

    /**
     * @const Flag for brackets
     */
    const CHAR_BRACKETS = 128;

    /**
     * @const Flag for punctuation marks
     */
    const CHAR_PUNCT = 256;

    /**
     * @const Flag for upper/lower-case and digits but without "B8G6I1l|0OQDS5Z2"
     */
    const EASY_TO_READ = 512;

    /**
     * @var Mixer The mixing strategy to use for this generator instance
     */
    protected $mixer = null;

    /**
     * @var array An array of random number sources to use for this generator
     */
    protected $sources = array();

    /**
     * @var array The different characters, by Flag
     */
    protected $charArrays = array(
        self::CHAR_UPPER     => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
        self::CHAR_LOWER     => 'abcdefghijklmnopqrstuvwxyz',
        self::CHAR_DIGITS    => '0123456789',
        self::CHAR_UPPER_HEX => 'ABCDEF',
        self::CHAR_LOWER_HEX => 'abcdef',
        self::CHAR_BASE64    => '+/',
        self::CHAR_SYMBOLS   => '!"#$%&\'()* +,-./:;<=>?@[\]^_`{|}~',
        self::CHAR_BRACKETS  => '()[]{}<>',
        self::CHAR_PUNCT     => ',.;:',
    );

    /**
     * @internal
     * @private
     * @const string Ambiguous characters for "Easy To Read" sets
     */
    const AMBIGUOUS_CHARS = 'B8G6I1l|0OQDS5Z2()[]{}:;,.';

    /**
     * Build a new instance of the generator
     *
     * @param array $sources An array of random data sources to use
     * @param Mixer $mixer   The mixing strategy to use for this generator
     */
    public function __construct(array $sources, Mixer $mixer)
    {
        foreach ($sources as $source) {
            $this->addSource($source);
        }
        $this->mixer = $mixer;
    }

    /**
     * Add a random number source to the generator
     *
     * @param Source $source The random number source to add
     *
     * @return Generator $this The current generator instance
     */
    public function addSource(Source $source)
    {
        $this->sources[] = $source;

        return $this;
    }

    /**
     * Generate a random number (string) of the requested size
     *
     * @param int $size The size of the requested random number
     *
     * @return string The generated random number (string)
     */
    public function generate($size)
    {
        $seeds = array();
        foreach ($this->sources as $source) {
            $seeds[] = $source->generate($size);
        }

        return $this->mixer->mix($seeds);
    }

    /**
     * Generate a random integer with the given range
     *
     * @param int $min The lower bound of the range to generate
     * @param int $max The upper bound of the range to generate
     *
     * @return int The generated random number within the range
     */
    public function generateInt($min = 0, $max = PHP_INT_MAX)
    {
        $tmp   = (int) max($max, $min);
        $min   = (int) min($max, $min);
        $max   = $tmp;
        $range = $max - $min;
        if ($range == 0) {
            return $max;
        } elseif ($range > PHP_INT_MAX || is_float($range) || $range < 0) {
            /**
             * This works, because PHP will auto-convert it to a float at this point,
             * But on 64 bit systems, the float won't have enough precision to
             * actually store the difference, so we need to check if it's a float
             * and hence auto-converted...
             */
            throw new \RangeException(
                'The supplied range is too great to generate'
            );
        }

        $bits  = $this->countBits($range) + 1;
        $bytes = (int) max(ceil($bits / 8), 1);
        if ($bits == 63) {
            /**
             * Fixes issue #22
             *
             * @see https://github.com/ircmaxell/RandomLib/issues/22
             */
            $mask = 0x7fffffffffffffff;
        } else {
            $mask = (int) (pow(2, $bits) - 1);
        }

        /**
         * The mask is a better way of dropping unused bits.  Basically what it does
         * is to set all the bits in the mask to 1 that we may need.  Since the max
         * range is PHP_INT_MAX, we will never need negative numbers (which would
         * have the MSB set on the max int possible to generate).  Therefore we
         * can just mask that away.  Since pow returns a float, we need to cast
         * it back to an int so the mask will work.
         *
         * On a 64 bit platform, that means that PHP_INT_MAX is 2^63 - 1.  Which
         * is also the mask if 63 bits are needed (by the log(range, 2) call).
         * So if the computed result is negative (meaning the 64th bit is set), the
         * mask will correct that.
         *
         * This turns out to be slightly better than the shift as we don't need to
         * worry about "fixing" negative values.
         */
        do {
            $test   = $this->generate($bytes);
            $result = hexdec(bin2hex($test)) & $mask;
        } while ($result > $range);

        return $result + $min;
    }

    /**
     * Generate a random string of specified length.
     *
     * This uses the supplied character list for generating the new result
     * string.
     *
     * @param int   $length     The length of the generated string
     * @param mixed $characters String: An optional list of characters to use
     *                          Integer: Character flags
     *
     * @return string The generated random string
     */
    public function generateString($length, $characters = '')
    {
        if (is_int($characters)) {
            // Combine character sets
            $characters = $this->expandCharacterSets($characters);
        }
        if ($length == 0 || strlen($characters) == 1) {
            return '';
        } elseif (empty($characters)) {
            // Default to base 64
            $characters = $this->expandCharacterSets(self::CHAR_BASE64);
        }

        // determine how many bytes to generate
        // This is basically doing floor(log(strlen($characters)))
        // But it's fixed to work properly for all numbers
        $len   = strlen($characters);

        // The max call here fixes an issue where we under-generate in cases
        // where less than 8 bits are needed to represent $len
        $bytes = $length * ceil(($this->countBits($len)) / 8);

        // determine mask for valid characters
        $mask   = 256 - (256 % $len);

        $result = '';
        do {
            $rand = $this->generate($bytes);
            for ($i = 0; $i < $bytes; $i++) {
                if (ord($rand[$i]) >= $mask) {
                    continue;
                }
                $result .= $characters[ord($rand[$i]) % $len];
            }
        } while (strlen($result) < $length);
        // We may over-generate, since we always use the entire buffer
        return substr($result, 0, $length);
    }

    /**
     * Get the Mixer used for this instance
     *
     * @return Mixer the current mixer
     */
    public function getMixer()
    {
        return $this->mixer;
    }

    /**
     * Get the Sources used for this instance
     *
     * @return Source[] the current mixer
     */
    public function getSources()
    {
        return $this->sources;
    }

    /**
     * Count the minimum number of bits to represent the provided number
     *
     * This is basically floor(log($number, 2))
     * But avoids float precision issues
     *
     * @param int $number The number to count
     *
     * @return int The number of bits
     */
    protected function countBits($number)
    {
        $log2 = 0;
        while ($number >>= 1) {
            $log2++;
        }

        return $log2;
    }

    /**
     * Expand a character set bitwise spec into a string character set
     *
     * This will also replace EASY_TO_READ characters if the flag is set
     *
     * @param int $spec The spec to expand (bitwise combination of flags)
     *
     * @return string The expanded string
     */
    protected function expandCharacterSets($spec)
    {
        $combined = '';
        if ($spec == self::EASY_TO_READ) {
            $spec |= self::CHAR_ALNUM;
        }
        foreach ($this->charArrays as $flag => $chars) {
            if ($flag == self::EASY_TO_READ) {
                // handle this later
                continue;
            }
            if (($spec & $flag) === $flag) {
                $combined .= $chars;
            }
        }
        if ($spec & self::EASY_TO_READ) {
            // remove ambiguous characters
            $combined = str_replace(str_split(self::AMBIGUOUS_CHARS), '', $combined);
        }

        return count_chars($combined, 3);
    }
}
