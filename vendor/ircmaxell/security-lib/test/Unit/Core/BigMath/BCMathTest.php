<?php

require_once __DIR__ . '/../BigMathTest.php';

class Unit_Core_BigMath_BCMathTest extends Unit_Core_BigMathTest {
    
    protected static $mathImplementations = array();
    
    protected function setUp() {
        if (!extension_loaded('bcmath')) {
            $this->markTestSkipped('BCMath is not loaded');
        }
    }
    
    /**
     * @dataProvider provideAddTest
     */
    public function testAdd($left, $right, $expected) {
        $obj = new \SecurityLib\BigMath\BCMath;
        $this->assertEquals($expected, $obj->add($left, $right));
    }

    /**
     * @dataProvider provideSubtractTest
     */
    public function testSubtract($left, $right, $expected) {
        $obj = new \SecurityLib\BigMath\BCMath;
        $this->assertEquals($expected, $obj->subtract($left, $right));
    }
}