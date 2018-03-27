<?php

/*
 * The RandomLib library for securely generating random numbers and strings in PHP
 *
 * @author     Anthony Ferrara <ircmaxell@ircmaxell.com>
 * @copyright  2011 The Authors
 * @license    http://www.opensource.org/licenses/mit-license.html  MIT License
 * @version    Build @@version@@
 */
namespace RandomLib\Source;

use SecurityLib\Strength;

class MTRandTest extends AbstractSourceTest
{
    protected static function getExpectedStrength()
    {
        if (defined('S_ALL')) {
            return new Strength(Strength::MEDIUM);
        } else {
            return new Strength(Strength::LOW);
        }
    }
}
