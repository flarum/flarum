<?php

use SecurityLib\BaseConverter;

class Unit_Core_BaseConverterTest extends PHPUnit_Framework_TestCase {

    public static function provideConvertFromBinary() {
        $return = array(
            array('', '', ''),
            array(chr(0), '012', '0'),
            array(chr(9), '01', '1001'),
            array(chr(1) . chr(2) . chr(3), '0123456789', '66051'),
        );
        return $return;
    }

    public static function provideConvertToFromBinary() {
        $return = array();
        $str = chr(1) . chr(0);
        for ($i = 2; $i < 256; $i++) {
             $str .= chr($i);
             $return[] = array($str, strrev($str));
        }
        return $return;
    }

    /**
     * @covers SecurityLib\BaseConverter::convertFromBinary
     * @covers SecurityLib\BaseConverter::baseConvert
     * @dataProvider provideConvertFromBinary
     */
    public function testConvertFromBinary($from, $to, $expect) {
        $result = BaseConverter::convertFromBinary($from, $to);
        $this->assertEquals($expect, $result);
    }

    /**
     * @covers SecurityLib\BaseConverter::convertToBinary
     * @covers SecurityLib\BaseConverter::baseConvert
     * @dataProvider provideConvertFromBinary
     */
    public function testConvertToBinary($expect, $from, $str) {
        $result = BaseConverter::convertToBinary($str, $from);
        $result = ltrim($result, chr(0));
        $expect = ltrim($expect, chr(0));
        $this->assertEquals($expect, $result);
    }

    /**
     * @covers SecurityLib\BaseConverter::convertToBinary
     * @covers SecurityLib\BaseConverter::convertFromBinary
     * @covers SecurityLib\BaseConverter::baseConvert
     * @dataProvider provideConvertToFromBinary
     */
    public function testConvertToAndFromBinary($str, $from) {
return false;
        $result1 = BaseConverter::convertFromBinary($str, $from);
        $result = BaseConverter::convertToBinary($result1, $from);
        $this->assertEquals($str, $result);
    }

    /**
     * @covers SecurityLib\BaseConverter::baseConvert
     * @expectedException InvalidArgumentException
     */
    public function testBaseConvertFailure() {
        BaseConverter::baseConvert(array(1), 1, 1);
    }
}
