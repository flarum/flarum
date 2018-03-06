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
 * The Mixer strategy interface.
 *
 * All mixing strategies must implement this interface
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
 * The Mixer strategy interface.
 *
 * All mixing strategies must implement this interface
 *
 * @category   PHPPasswordLib
 * @package    Random
 *
 * @author     Anthony Ferrara <ircmaxell@ircmaxell.com>
 */
class Mixer extends \RandomLibTest\Mocks\AbstractMock implements \RandomLib\Mixer
{
    public static $strength = null;

    public static $test = true;

    public static function init()
    {
        static::$strength = new Strength(Strength::HIGH);
        static::$test = true;
    }

    /**
     * Return an instance of Strength indicating the strength of the mixer
     *
     * @return \SecurityLib\Strength An instance of one of the strength classes
     */
    public static function getStrength()
    {
        return static::$strength;
    }

    /**
     * Test to see if the mixer is available
     *
     * @return bool If the mixer is available on the system
     */
    public static function test()
    {
        return static::$test;
    }

    /**
     * Mix the provided array of strings into a single output of the same size
     *
     * All elements of the array should be the same size.
     *
     * @param array $parts The parts to be mixed
     *
     * @return string The mixed result
     */
    public function mix(array $parts)
    {
        return $this->__call('mix', array($parts));
    }
}
