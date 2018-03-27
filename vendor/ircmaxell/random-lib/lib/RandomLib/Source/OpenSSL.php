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
 * The OpenSSL Random Number Source
 *
 * This uses the OS's secure generator to generate high strength numbers
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
 * The OpenSSL Random Number Source
 *
 * This uses the OS's secure generator to generate high strength numbers
 *
 * @category   PHPCryptLib
 * @package    Random
 * @subpackage Source
 *
 * @author     Anthony Ferrara <ircmaxell@ircmaxell.com>
 * @codeCoverageIgnore
 */
class OpenSSL extends \RandomLib\AbstractSource
{

    /**
     * Return an instance of Strength indicating the strength of the source
     *
     * @return \SecurityLib\Strength An instance of one of the strength classes
     */
    public static function getStrength()
    {
        /**
         * Prior to PHP 5.6.12 (see https://bugs.php.net/bug.php?id=70014) the "openssl_random_pseudo_bytes"
         * was using "RAND_pseudo_bytes" (predictable) instead of "RAND_bytes" (unpredictable).
         * Release notes: http://php.net/ChangeLog-5.php#5.6.12
         */
        if (PHP_VERSION_ID >= 50612) {
            return new Strength(Strength::HIGH);
        }
        
        /**
         * Prior to PHP 5.5.28 (see https://bugs.php.net/bug.php?id=70014) the "openssl_random_pseudo_bytes"
         * was using "RAND_pseudo_bytes" (predictable) instead of "RAND_bytes" (unpredictable).
         * Release notes: http://php.net/ChangeLog-5.php#5.5.28
         */
        if (PHP_VERSION_ID >= 50528 && PHP_VERSION_ID < 50600) {
            return new Strength(Strength::HIGH);
        }
        
        /**
         * Prior to PHP 5.4.44 (see https://bugs.php.net/bug.php?id=70014) the "openssl_random_pseudo_bytes"
         * was using "RAND_pseudo_bytes" (predictable) instead of "RAND_bytes" (unpredictable).
         * Release notes: http://php.net/ChangeLog-5.php#5.4.44
         */
        if (PHP_VERSION_ID >= 50444 && PHP_VERSION_ID < 50500) {
            return new Strength(Strength::HIGH);
        }
        
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
        return function_exists('openssl_random_pseudo_bytes');
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
        if ($size < 1) {
            return str_repeat(chr(0), $size);
        }
        /**
         * Note, normally we would check the return of of $crypto_strong to
         * ensure that we generated a good random string.  However, since we're
         * using this as one part of many sources a low strength random number
         * shouldn't be much of an issue.
         */
        return openssl_random_pseudo_bytes($size);
    }
}
