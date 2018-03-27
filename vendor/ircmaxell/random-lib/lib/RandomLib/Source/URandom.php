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
 * The URandom Random Number Source
 *
 * This uses the *nix /dev/urandom device to generate medium strength numbers
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
 * The URandom Random Number Source
 *
 * This uses the *nix /dev/urandom device to generate medium strength numbers
 *
 * @category   PHPCryptLib
 * @package    Random
 * @subpackage Source
 *
 * @author     Anthony Ferrara <ircmaxell@ircmaxell.com>
 * @codeCoverageIgnore
 */
class URandom extends \RandomLib\AbstractSource
{

    /**
     * @var string The file to read from
     */
    protected static $file = '/dev/urandom';

    /**
     * Return an instance of Strength indicating the strength of the source
     *
     * @return \SecurityLib\Strength An instance of one of the strength classes
     */
    public static function getStrength()
    {
        return new Strength(Strength::MEDIUM);
    }

    /**
     * If the source is currently available.
     * Reasons might be because the library is not installed
     *
     * @return bool
     */
    public static function isSupported()
    {
        return @file_exists(static::$file);
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
        if ($size == 0) {
            return static::emptyValue($size);
        }
        $file = fopen(static::$file, 'rb');
        if (!$file) {
            return static::emptyValue($size);
        }
        if (function_exists('stream_set_read_buffer')) {
            stream_set_read_buffer($file, 0);
        }
        $result = fread($file, $size);
        fclose($file);

        return $result;
    }
}
