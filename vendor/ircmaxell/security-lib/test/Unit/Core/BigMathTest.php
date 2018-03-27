<?php

class Unit_Core_BigMathTest extends PHPUnit_Framework_TestCase {

    protected static $mathImplementations = array();

    public static function provideAddTest() {
        $ret = array(
            array('1', '1', '2'),
            array('11', '11', '22'),
            array('1111111111', '1111111111', '2222222222'),
            array('555', '555', '1110'),
            array('-10', '10', '0'),
            array('10', '-10', '0'),
            array('-10', '-10', '-20'),
            array('0', '0', '0'),
            array('5', '0', '5'),
        );
        return $ret;
    }

    public static function provideSubtractTest() {
        return array(
            array('1', '1', '0'),
            array('6', '3', '3'),
            array('200', '250', '-50'),
            array('10', '300', '-290'),
            array('-1', '-1', '0'),
            array('-5', '5', '-10'),
            array('5', '-5', '10'),
            array('0', '0', '0'),
            array('5', '0', '5'),
        );
    }

    public function testCreateFromServerConfiguration() {
        $instance = \SecurityLib\BigMath::createFromServerConfiguration();
        if (extension_loaded('bcmath')) {
            $this->assertEquals('SecurityLib\\BigMath\\BCMath', get_class($instance));
        } elseif (extension_loaded('gmp')) {
            $this->assertEquals('SecurityLib\\BigMath\\GMP', get_class($instance));
        } else {
            $this->assertEquals('SecurityLib\\BigMath\\PHPMath', get_class($instance));
        }
    }
}