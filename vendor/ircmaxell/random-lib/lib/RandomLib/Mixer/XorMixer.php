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
 * The Hash medium strength mixer class
 *
 * This class implements a mixer based upon the recommendations in RFC 4086
 * section 5.2
 *
 * PHP version 5.3
 *
 * @see        http://tools.ietf.org/html/rfc4086#section-5.2
 *
 * @category   PHPCryptLib
 * @package    Random
 * @subpackage Mixer
 *
 * @author     Anthony Ferrara <ircmaxell@ircmaxell.com>
 * @copyright  2011 The Authors
 * @license    http://www.opensource.org/licenses/mit-license.html  MIT License
 *
 * @version    Build @@version@@
 */
namespace RandomLib\Mixer;

use SecurityLib\Strength;

/**
 * The Hash medium strength mixer class
 *
 * This class implements a mixer based upon the recommendations in RFC 4086
 * section 5.2
 *
 * @see        http://tools.ietf.org/html/rfc4086#section-5.2
 *
 * @category   PHPCryptLib
 * @package    Random
 * @subpackage Mixer
 *
 * @author     Anthony Ferrara <ircmaxell@ircmaxell.com>
 */
class XorMixer extends \RandomLib\AbstractMixer
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
     * Test to see if the mixer is available
     *
     * @return bool If the mixer is available on the system
     */
    public static function test()
    {
        return true;
    }

    /**
     * Get the block size (the size of the individual blocks used for the mixing)
     *
     * @return int The block size
     */
    protected function getPartSize()
    {
        return 64;
    }

    /**
     * Mix 2 parts together using one method
     *
     * @param string $part1 The first part to mix
     * @param string $part2 The second part to mix
     *
     * @return string The mixed data
     */
    protected function mixParts1($part1, $part2)
    {
        return $part1 ^ $part2;
    }

    /**
     * Mix 2 parts together using another different method
     *
     * @param string $part1 The first part to mix
     * @param string $part2 The second part to mix
     *
     * @return string The mixed data
     */
    protected function mixParts2($part1, $part2)
    {
        // Both mixers are identical, this is for speed, not security
        return $part1 ^ $part2;
    }
}
