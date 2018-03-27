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

class UniqIDTest extends AbstractSourceTest
{
    protected static function getExpectedStrength()
    {
        return new Strength(Strength::LOW);
    }
}
