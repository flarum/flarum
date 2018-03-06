<?php
namespace SecurityLib;

/**
 * Highly recommended that his be run with and without setting
 * mbstring.func_overload = 7
 * in your php.ini file to verify its correctness.
 */
class UtilTest extends \PHPUnit_Framework_TestCase {

    public function testSafeStrlen() {
        $this->assertEquals(Util::safeStrlen("\x03\x3f"), 2);
    }

    public function testSafeSubstr() {
        $a = "abcdefg\x03\x3fhijk";
        $b = "\x03\x3f";
        $this->assertEquals(Util::safeSubstr($a, 7, 2), $b);
    }
}
