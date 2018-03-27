<?php

use SecurityLibTest\Mocks\Enum;

class Unit_Core_EnumTest extends PHPUnit_Framework_TestCase {

    public static function provideTestCompare() {
        return array(
            array(new Enum(Enum::Value1), new Enum(Enum::Value1), 0),
            array(new Enum(Enum::Value2), new Enum(Enum::Value1), -1),
            array(new Enum(Enum::Value1), new Enum(Enum::Value2), 1),
        );
    }

    /**
     * @expectedException UnexpectedValueException
     */
    public function testConstructFail() {
        $obj = new Enum();
    }
    public function testConstruct() {
        $obj = new Enum(Enum::Value3);
        $this->assertTrue($obj instanceof \SecurityLib\Enum);
    }

    public function testToString() {
        $obj = new Enum(Enum::Value3);
        $this->assertEquals('3', (string) $obj);
    }

    /**
     * @covers SecurityLib\Core\Enum::compare
     * @dataProvider provideTestCompare
     */
    public function testCompare(Enum $from, Enum $to, $expected) {
        $this->assertEquals($expected, $from->compare($to));
    }

    public function testGetConstList() {
        $obj = new Enum(Enum::Value3);
        $const = $obj->getConstList();
        $this->assertEquals(array(
            'Value1' => 1,
            'Value2' => 2,
            'Value3' => 3,
            'Value4' => 4,
        ), $const);
    }

    public function testGetConstListWithDefault() {
        $obj = new Enum(Enum::Value3);
        $const = $obj->getConstList(true);
        $this->assertEquals(array(
            '__DEFAULT' => null,
            'Value1' => 1,
            'Value2' => 2,
            'Value3' => 3,
            'Value4' => 4,
        ), $const);
    }
}