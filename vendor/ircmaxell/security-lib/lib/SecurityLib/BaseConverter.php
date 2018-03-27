<?php

/**
 * A Utility class for converting between raw binary strings and a given
 * list of characters
 *
 * PHP version 5.3
 *
 * @category   PHPSecurityLib
 * @package    Core
 * @author     Anthony Ferrara <ircmaxell@ircmaxell.com>
 * @copyright  2011 The Authors
 * @license    http://www.opensource.org/licenses/mit-license.html  MIT License
 * @version    Build @@version@@
 */

namespace SecurityLib;

/**
 * A Utility class for converting between raw binary strings and a given
 * list of characters
 *
 * @category   PHPSecurityLib
 * @package    Core
 * @author     Anthony Ferrara <ircmaxell@ircmaxell.com>
 */
class BaseConverter {

    /**
     * Convert from a raw binary string to a string of characters
     *
     * @param string $string     The string to convert from
     * @param string $characters The list of characters to convert to
     *
     * @return string The converted string
     */
    public static function convertFromBinary($string, $characters) {
        if ($string === '' || empty($characters)) {
            return '';
        }
        $string   = str_split($string);
        $callback = function($str) {
                return ord($str);
            };
        $string    = array_map($callback, $string);
        $converted = static::baseConvert($string, 256, strlen($characters));
        $callback  = function ($num) use ($characters) {
                return $characters[$num];
            };
        $ret = implode('', array_map($callback, $converted));
        return $ret;
    }

    /**
     * Convert to a raw binary string from a string of characters
     *
     * @param string $string     The string to convert from
     * @param string $characters The list of characters to convert to
     *
     * @return string The converted string
     */
    public static function convertToBinary($string, $characters) {
        if (empty($string) || empty($characters)) {
            return '';
        }
        $string   = str_split($string);
        $callback = function($str) use ($characters) {
                return strpos($characters, $str);
            };
        $string    = array_map($callback, $string);
        $converted = static::baseConvert($string, strlen($characters), 256);
        $callback  = function ($num) {
                return chr($num);
            };
        return implode('', array_map($callback, $converted));
    }

    /**
     * Convert an array of input blocks to another numeric base
     *
     * This function was modified from an implementation found on StackOverflow.
     * Special Thanks to @KeithRandall for supplying the implementation.
     *
     * @param int[] $source  The source number, as an array
     * @param int   $srcBase The source base as an integer
     * @param int   $dstBase The destination base as an integer
     *
     * @see http://codegolf.stackexchange.com/questions/1620/arb/1626#1626
     * @return int[] An array of integers in the encoded base
     */
    public static function baseConvert(array $source, $srcBase, $dstBase) {
        if ($dstBase < 2) {
            $message = sprintf('Invalid Destination Base: %d', $dstBase);
            throw new \InvalidArgumentException($message);
        }
        $result = array();
        $count  = count($source);
        while ($count) {
            $itMax     = $count;
            $remainder = $count = $loop = 0;
            while($loop < $itMax) {
                $dividend  = $source[$loop++] + $remainder * $srcBase;
                $remainder = $dividend % $dstBase;
                $res       = ($dividend - $remainder) / $dstBase;
                if ($count || $res) {
                    $source[$count++] = $res;
                }
            }
            $result[] = $remainder;
        }
        return array_reverse($result);
    }

}
