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
 * The Rand Random Number Source
 *
 * This source generates low strength random numbers by using the internal
 * rand() function.  By itself it is quite weak.  However when combined with
 * other sources it does provide significant benefit.
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
 * The Rand Random Number Source
 *
 * This source generates low strength random numbers by using the internal
 * rand() function.  By itself it is quite weak.  However when combined with
 * other sources it does provide significant benefit.
 *
 * @category   PHPCryptLib
 * @package    Random
 * @subpackage Source
 *
 * @author     Anthony Ferrara <ircmaxell@ircmaxell.com>
 * @codeCoverageIgnore
 */
class Rand extends \RandomLib\AbstractSource
{

    /**
     * Return an instance of Strength indicating the strength of the source
     *
     * @return \SecurityLib\Strength An instance of one of the strength classes
     */
    public static function getStrength()
    {
        // Detect if Suhosin Hardened PHP patch is applied
        if (defined('S_ALL')) {
            return new Strength(Strength::LOW);
        } else {
            return new Strength(Strength::VERYLOW);
        }
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
        $result = '';
        for ($i = 0; $i < $size; $i++) {
            $result .= chr((rand() ^ rand()) % 256);
        }

        return $result;
    }
}
