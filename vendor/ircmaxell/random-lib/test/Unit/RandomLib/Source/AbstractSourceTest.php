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

abstract class AbstractSourceTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $class = static::getTestedClass();

        if (!$class::isSupported()) {
            $this->markTestSkipped();
        }
    }

    protected static function getTestedClass()
    {
        return preg_replace('/Test$/', '', get_called_class());
    }

    protected static function getExpectedStrength()
    {
        return new Strength(Strength::VERYLOW);
    }

    public static function provideGenerate()
    {
        $data = array();
        for ($i = 0; $i < 100; $i += 5) {
            $not = $i > 0 ? str_repeat(chr(0), $i) : chr(0);
            $data[] = array($i, $not);
        }

        return $data;
    }

    public function testGetStrength()
    {
        $class = static::getTestedClass();
        $strength = static::getExpectedStrength();
        $actual = $class::getStrength();
        $this->assertEquals($actual, $strength);
    }

    /**
     * @dataProvider provideGenerate
     * @group slow
     */
    public function testGenerate($length, $not)
    {
        $class = static::getTestedClass();
        $rand = new $class();
        $stub = $rand->generate($length);
        $this->assertEquals($length, strlen($stub));
        $this->assertNotEquals($not, $stub);
    }
}
