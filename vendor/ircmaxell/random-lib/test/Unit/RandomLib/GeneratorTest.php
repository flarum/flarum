<?php

/*
 * The RandomLib library for securely generating random numbers and strings in PHP
 *
 * @author     Anthony Ferrara <ircmaxell@ircmaxell.com>
 * @copyright  2011 The Authors
 * @license    http://www.opensource.org/licenses/mit-license.html  MIT License
 * @version    Build @@version@@
 */
namespace RandomLib;

class GeneratorTest extends \PHPUnit_Framework_TestCase
{
    protected $generator = null;
    protected $mixer = null;
    protected $sources = array();

    public static function provideGenerate()
    {
        return array(
            array(0, ''),
            array(1, chr(0)),
            array(2, chr(1) . chr(1)),
            array(3, chr(2) . chr(0) . chr(2)),
            array(4, chr(3) . chr(3) . chr(3) . chr(3)),
        );
    }

    public static function provideGenerateInt()
    {
        return array(
            array(1, 1, 1),
            array(0, 1, 0),
            array(0, 255, 0),
            array(400, 655, 400),
            array(0, 65535, 257),
            array(65535, 131070, 65792),
            array(0, 16777215, (2<<16) + 2),
            array(-10, 0, -10),
            array(-655, -400, -655),
            array(-131070, -65535, -130813),
        );
    }

    public static function provideGenerateIntRangeTest()
    {
        return array(
            array(0, 0),
            array(0, 1),
            array(1, 10000),
            array(100000, \PHP_INT_MAX),
        );
    }

    public static function provideGenerateStringTest()
    {
        return array(
            array(0, 'ab', ''),
            array(1, 'ab', 'a'),
            array(1, 'a', ''),
            array(2, 'ab', 'bb'),
            array(3, 'abc', 'cac'),
            array(8, '0123456789abcdef', '77777777'),
            array(16, '0123456789abcdef', 'ffffffffffffffff'),
            array(16, '', 'DDDDDDDDDDDDDDDD'),
        );
    }

    public function setUp()
    {
        $source1 = $this->getMock('RandomLib\Source');
        $source1->expects($this->any())
            ->method('generate')
            ->will($this->returnCallback(function ($size) {
                $r = '';
                for ($i = 0; $i < $size; $i++) {
                    $r .= chr($i);
                }

                return $r;
            }
        ));
        $source2 = $this->getMock('RandomLib\Source');
        $source2->expects($this->any())
            ->method('generate')
            ->will($this->returnCallback(function ($size) {
                $r = '';
                for ($i = $size - 1; $i >= 0; $i--) {
                    $r .= chr($i);
                }

                return $r;
            }
        ));

        $this->mixer = $this->getMock('RandomLib\Mixer');
        $this->mixer->expects($this->any())
            ->method('mix')
            ->will($this->returnCallback(function (array $sources) {
                if (empty($sources)) {
                    return '';
                }
                $start = array_pop($sources);

                return array_reduce(
                    $sources,
                    function ($el1, $el2) {
                        return $el1 ^ $el2;
                    },
                    $start
                );
            }));

        $this->sources = array($source1, $source2);
        $this->generator = new Generator($this->sources, $this->mixer);
    }

    public function testConstruct()
    {
        $this->assertTrue($this->generator instanceof Generator);
    }

    public function testGetMixer()
    {
        $this->assertSame($this->mixer, $this->generator->getMixer());
    }

    public function testGetSources()
    {
        $this->assertSame($this->sources, $this->generator->getSources());
    }

    /**
     * @dataProvider provideGenerate
     */
    public function testGenerate($size, $expect)
    {
        $this->assertEquals($expect, $this->generator->generate($size));
    }

    /**
     * @dataProvider provideGenerateInt
     */
    public function testGenerateInt($min, $max, $expect)
    {
        $this->assertEquals($expect, $this->generator->generateInt($min, $max));
    }

    /**
     * @dataProvider provideGenerateIntRangeTest
     */
    public function testGenerateIntRange($min, $max)
    {
        $n = $this->generator->generateInt($min, $max);
        $this->assertTrue($min <= $n);
        $this->assertTrue($max >= $n);
    }

    /**
     * @expectedException RangeException
     */
    public function testGenerateIntFail()
    {
        $n = $this->generator->generateInt(-1, PHP_INT_MAX);
    }

    
    public function testGenerateIntLargeTest()
    {
        $bits = 30;
        $expected = 50529027;
        if (PHP_INT_MAX > 4000000000) {
            $bits = 55;
            $expected = 1693273676973062;
        }
        $n = $this->generator->generateInt(0, (int) pow(2, $bits));
        $this->assertEquals($expected, $n);
    }
    
    /**
     * @dataProvider provideGenerateStringTest
     */
    public function testGenerateString($length, $chars, $expected)
    {
        $n = $this->generator->generateString($length, $chars);
        $this->assertEquals($expected, $n);
    }

    /**
     * This test checks for issue #22:
     *
     * @see https://github.com/ircmaxell/RandomLib/issues/22
     */
    public function testGenerateLargeRange()
    {
        if (PHP_INT_MAX < pow(2, 32)) {
            $this->markTestSkipped("Only test on 64 bit platforms");
        }
        $this->assertEquals(506381209866536711, $this->generator->generateInt(0, PHP_INT_MAX));
    }
}
