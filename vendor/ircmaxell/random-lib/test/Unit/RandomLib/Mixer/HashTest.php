<?php

/*
 * The RandomLib library for securely generating random numbers and strings in PHP
 *
 * @author     Anthony Ferrara <ircmaxell@ircmaxell.com>
 * @copyright  2011 The Authors
 * @license    http://www.opensource.org/licenses/mit-license.html  MIT License
 * @version    Build @@version@@
 */
namespace RandomLib\Mixer;

use SecurityLib\Strength;

class HashTest extends \PHPUnit_Framework_TestCase
{
    public static function provideMix()
    {
        $data = array(
            array(array(), ''),
            array(array('1', '1'), '0d'),
            array(array('a'), '61'),
            // This expects 'b' because of how the mock hmac function works
            array(array('a', 'b'), '9a'),
            array(array('aa', 'ba'), '6e84'),
            array(array('ab', 'bb'), 'b0cb'),
            array(array('aa', 'bb'), 'ae8d'),
            array(array('aa', 'bb', 'cc'), 'a14c'),
            array(array('aabbcc', 'bbccdd', 'ccddee'), 'a8aff3939934'),
        );

        return $data;
    }

    public function testConstructWithoutArgument()
    {
        $hash = new Hash();
        $this->assertTrue($hash instanceof \RandomLib\Mixer);
    }

    public function testGetStrength()
    {
        $strength = new Strength(Strength::MEDIUM);
        $actual = Hash::getStrength();
        $this->assertEquals($actual, $strength);
    }

    public function testTest()
    {
        $actual = Hash::test();
        $this->assertTrue($actual);
    }

    /**
     * @dataProvider provideMix
     */
    public function testMix($parts, $result)
    {
        $mixer = new Hash('md5');
        $actual = $mixer->mix($parts);
        $this->assertEquals($result, bin2hex($actual));
    }
}
