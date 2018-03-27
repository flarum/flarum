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

class SodiumTest extends \PHPUnit_Framework_TestCase
{
    public static function provideGenerate()
    {
        $data = array();
        for ($i = 1; $i < 100; $i += 5) {
            $not = str_repeat(chr(0), $i);
            $data[] = array($i, $not);
        }

        return $data;
    }

    
    public function testGetStrength()
    {
        $strength = new Strength(Strength::HIGH);
        $actual = Sodium::getStrength();
        $this->assertEquals($actual, $strength);
    }

    /**
     * @dataProvider provideGenerate
     */
    public function testGenerate($length, $not)
    {
        if (!extension_loaded('libsodium')) {
            $this->markTestSkipped('The libsodium extension is not loaded');
        }

        $rand = new Sodium();
        $stub = $rand->generate($length);
        $this->assertEquals($length, strlen($stub));
        $this->assertNotEquals($not, $stub);
    }

    /**
     * @dataProvider provideGenerate
     */
    public function testGenerateWithoutLibsodium($length, $not)
    {
        $rand = new Sodium(false);
        $stub = $rand->generate($length);
        $this->assertEquals($length, strlen($stub));
        $this->assertEquals($not, $stub);
    }

    public function testGenerateWithZeroLength()
    {
        if (!extension_loaded('libsodium')) {
            $this->markTestSkipped('The libsodium extension is not loaded');
        }

        $rand = new Sodium();
        $stub = $rand->generate(0);
        $this->assertEquals(0, strlen($stub));
        $this->assertEquals('', $stub);
    }

    public function testGenerateWithZeroLengthWithoutLibsodium()
    {
        $rand = new Sodium(false);
        $stub = $rand->generate(0);
        $this->assertEquals(0, strlen($stub));
        $this->assertEquals('', $stub);
    }
}
