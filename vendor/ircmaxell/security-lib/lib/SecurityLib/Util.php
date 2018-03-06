<?php
/**
 * The Mixer strategy interface.
 *
 * All mixing strategies must implement this interface
 *
 * PHP version 5.3
 *
 * @category   PHPPasswordLib
 * @package    Hash
 * @author     Anthony Ferrara <ircmaxell@ircmaxell.com>
 * @copyright  2011 The Authors
 * @license    http://www.opensource.org/licenses/mit-license.html  MIT License
 * @version    Build @@version@@
 */

namespace SecurityLib;

/**
 * The Utility trait.
 *
 * Contains methods used internally to this library.
 *
 * @category   PHPPasswordLib
 * @package    Random
 * @author     Scott Arciszewski <scott@arciszewski.me>
 * @license    http://www.opensource.org/licenses/mit-license.html  MIT License
 * @codeCoverageIgnore
 */
abstract class Util {

    /**
     * Return the length of a string, even in the presence of
     * mbstring.func_overload
     *
     * @param string $string the string we're measuring
     * @return int
     */
    public static function safeStrlen($string)
    {
        if (\function_exists('mb_strlen')) {
            return \mb_strlen($string, '8bit');
        }
        return \strlen($string);
    }

    /**
     * Return a string contained within a string, even in the presence of
     * mbstring.func_overload
     *
     * @param string $string The string we're searching
     * @param int $start What offset should we begin
     * @param int|null $length How long should the substring be?
     *                         (default: the remainder)
     * @return string
     */
    public static function safeSubstr($string, $start = 0, $length = null)
    {
        if (\function_exists('mb_substr')) {
            return \mb_substr($string, $start, $length, '8bit');
        } elseif ($length !== null) {
            return \substr($string, $start, $length);
        }
        return \substr($string, $start);
    }
}
