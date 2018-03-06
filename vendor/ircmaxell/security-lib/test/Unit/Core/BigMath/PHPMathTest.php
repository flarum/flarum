<?php

require_once __DIR__ . '/../BigMathTest.php';

class Unit_Core_BigMath_PHPMathTest extends Unit_Core_BigMathTest {
    
    protected static $mathImplementations = array();
    
    /**
     * @dataProvider provideAddTest
     */
    public function testAdd($left, $right, $expected) {
        $obj = new \SecurityLib\BigMath\PHPMath;
        $this->assertEquals($expected, $obj->add($left, $right));
    }
    
    /**
     * @dataProvider provideSubtractTest
     */
    public function testSubtract($left, $right, $expected) {
        $obj = new \SecurityLib\BigMath\PHPMath;
        $this->assertEquals($expected, $obj->subtract($left, $right));
    }
}