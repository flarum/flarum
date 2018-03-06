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
 * PHP version 5.3
 *
 * @category  PHPSecurityLib
 * @package   Random
 *
 * @author    Anthony Ferrara <ircmaxell@ircmaxell.com>
 * @copyright 2011 The Authors
 * @license    http://www.opensource.org/licenses/mit-license.html  MIT License
 *
 * @version   Build @@version@@
 */
namespace RandomLib;

use SecurityLib\Strength;

/**
 * An abstract mixer to implement a common mixing strategy
 *
 * @category PHPSecurityLib
 * @package  Random
 */
abstract class AbstractSource implements \RandomLib\Source
{

    /**
     * Return an instance of Strength indicating the strength of the source
     *
     * @return \SecurityLib\Strength An instance of one of the strength classes
     */
    public static function getStrength()
    {
        return new Strength(Strength::VERYLOW);
    }

    /**
     * If the source is currently available.
     * Reasons might be because the library is not installed
     *
     * @return bool
     */
    public static function isSupported()
    {
        return true;
    }

    /**
     * Returns a string of zeroes, useful when no entropy is available.
     *
     * @param int $size The size of the requested random string
     *
     * @return string A string of the requested size
     */
    protected static function emptyValue($size)
    {
        return str_repeat(chr(0), $size);
    }
}
