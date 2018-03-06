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
 * The libsodium Random Number Source
 *
 * This uses the libsodium secure generator to generate high strength numbers
 *
 * PHP version 5.3
 *
 * @category   PHPCryptLib
 * @package    Random
 * @subpackage Source
 *
 * @author     Anthony Ferrara <ircmaxell@ircmaxell.com>
 * @author     Ben Ramsey <ben@benramsey.com>
 * @copyright  2011 The Authors
 * @license    http://www.opensource.org/licenses/mit-license.html  MIT License
 *
 * @version    Build @@version@@
 *
 * @link       https://paragonie.com/book/pecl-libsodium
 * @link       http://pecl.php.net/package/libsodium
 */
namespace RandomLib\Source;

use SecurityLib\Strength;

/**
 * The libsodium Random Number Source
 *
 * This uses the libsodium secure generator to generate high strength numbers
 *
 * @category   PHPCryptLib
 * @package    Random
 * @subpackage Source
 *
 * @author     Anthony Ferrara <ircmaxell@ircmaxell.com>
 * @author     Ben Ramsey <ben@benramsey.com>
 */
class Sodium extends \RandomLib\AbstractSource
{

    /**
     * A property that may be forcibly set to `false` in the constructor, for
     * the purpose of testing this source
     *
     * @var bool
     */
    private $hasLibsodium = false;

    /**
     * Constructs a libsodium Random Number Source
     *
     * @param bool $useLibsodium May be set to `false` to disable libsodium for
     *                           testing purposes
     */
    public function __construct($useLibsodium = true)
    {
        if ($useLibsodium && extension_loaded('libsodium')) {
            $this->hasLibsodium = true;
        }
    }

    /**
     * If the source is currently available.
     * Reasons might be because the library is not installed
     *
     * @return bool
     */
    public static function isSupported()
    {
        return function_exists('Sodium\\randombytes_buf');
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
        if (!$this->hasLibsodium || $size < 1) {
            return str_repeat(chr(0), $size);
        }

        return \Sodium\randombytes_buf($size);
    }
}
