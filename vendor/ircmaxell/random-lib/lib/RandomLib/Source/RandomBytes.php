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
 * The PHP7 Random Number Source
 *
 * This uses the inbuilt PHP7 Random Bytes function
 *
 * PHP version 5.3
 *
 * @category   PHPCryptLib
 * @package    Random
 * @subpackage Source
 *
 * @author     Anthony Ferrara <ircmaxell@ircmaxell.com>
 * @copyright  2011 The Authors
 * @license    http://www.opensource.org/licenses/mit-license.html  MIT License
 *
 * @version    Build @@version@@
 */
namespace RandomLib\Source;

use SecurityLib\Strength;

/**
 * The PHP7 Random Number Source
 *
 * This uses the php7 secure generator to generate high strength numbers
 *
 * @category   PHPCryptLib
 * @package    Random
 * @subpackage Source
 *
 * @author     Anthony Ferrara <ircmaxell@ircmaxell.com>
 */
class RandomBytes extends \RandomLib\AbstractSource
{

    /**
     * If the source is currently available.
     * Reasons might be because the library is not installed
     *
     * @return bool
     */
    public static function isSupported()
    {
        return function_exists('random_bytes');
    }

    /**
     * Return an instance of Strength indicating the strength of the source
     *
     * @return Strength An instance of one of the strength classes
     */
    public static function getStrength()
    {
        return new Strength(Strength::HIGH);
    }

    /**
     * Generate a random string of the specified size
     *
     * @param int $size The size of the requested random string
     *
     * @return string A string of the requested size
     */
    public function generate($size)
    {
        if (!self::isSupported()) {
            return str_repeat(chr(0), $size);
        }

        return \random_bytes($size);
    }
}
