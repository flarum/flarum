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
 * The Random Number Source interface.
 *
 * All random number sources must implement this interface
 *
 * PHP version 5.3
 *
 * @category   PHPPasswordLib
 * @package    Random
 *
 * @author     Anthony Ferrara <ircmaxell@ircmaxell.com>
 * @copyright  2011 The Authors
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @license    http://www.gnu.org/licenses/lgpl-2.1.html LGPL v 2.1
 */
namespace RandomLibtest\Mocks\Random;

use SecurityLib\Strength;

/**
 * The Random Number Source interface.
 *
 * All random number sources must implement this interface
 *
 * @category   PHPPasswordLib
 * @package    Random
 *
 * @author     Anthony Ferrara <ircmaxell@ircmaxell.com>
 */
class Source extends \RandomLibTest\Mocks\AbstractMock implements \RandomLib\Source
{
    public static $strength = null;

    public static function init()
    {
        static::$strength = new Strength(Strength::VERYLOW);
    }

    /**
     * Return an instance of Strength indicating the strength of the source
     *
     * @return \SecurityLib\Strength An instance of one of the strength classes
     */
    public static function getStrength()
    {
        return static::$strength;
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
     * Generate a random string of the specified size
     *
     * Note: If the source fails to generate enough data, the result must be
     * padded to the requested length.
     *
     * @param int $size The size of the requested random string
     *
     * @return string A string of the requested size
     */
    public function generate($size)
    {
        return $this->__call('generate', array($size));
    }
}
