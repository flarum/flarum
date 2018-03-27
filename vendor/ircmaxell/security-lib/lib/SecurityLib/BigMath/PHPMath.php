<?php
/**
 * A class for arbitrary precision math functions implemented in PHP
 *
 * PHP version 5.3
 *
 * @category   PHPPasswordLib
 * @package    Core
 * @subpackage BigMath
 * @author     Anthony Ferrara <ircmaxell@ircmaxell.com>
 * @copyright  2011 The Authors
 * @license    http://www.opensource.org/licenses/mit-license.html  MIT License
 * @version    Build @@version@@
 */
namespace SecurityLib\BigMath;

use SecurityLib\BaseConverter;

/**
 * A class for arbitrary precision math functions implemented in PHP
 *
 * @category   PHPPasswordLib
 * @package    Core
 * @subpackage BigMath
 */
class PHPMath extends \SecurityLib\BigMath {

    /**
     * Add two numbers together
     *
     * @param string $left  The left argument
     * @param string $right The right argument
     *
     * @return A base-10 string of the sum of the two arguments
     */
    public function add($left, $right) {
        if (empty($left)) {
            return $right;
        } elseif (empty($right)) {
            return $left;
        }
        $negative = '';
        if ($left[0] == '-' && $right[0] == '-') {
            $negative = '-';
            $left     = substr($left, 1);
            $right    = substr($right, 1);
        } elseif ($left[0] == '-') {
            return $this->subtract($right, substr($left, 1));
        } elseif ($right[0] == '-') {
            return $this->subtract($left, substr($right, 1));
        }
        $left   = $this->normalize($left);
        $right  = $this->normalize($right);
        $result = BaseConverter::convertFromBinary(
            $this->addBinary($left, $right),
            '0123456789'
        );
        return $negative . $result;
    }

    /**
     * Subtract two numbers
     *
     * @param string $left  The left argument
     * @param string $right The right argument
     *
     * @return A base-10 string of the difference of the two arguments
     */
    public function subtract($left, $right) {
        if (empty($left)) {
            return $right;
        } elseif (empty($right)) {
            return $left;
        } elseif ($right[0] == '-') {
            return $this->add($left, substr($right, 1));
        } elseif ($left[0] == '-') {
            return '-' . $this->add(ltrim($left, '-'), $right);
        }
        $left    = $this->normalize($left);
        $right   = $this->normalize($right);
        $results = $this->subtractBinary($left, $right);
        $result  = BaseConverter::convertFromBinary($results[1], '0123456789');
        return $results[0] . $result;
    }

    /**
     * Add two binary strings together
     *
     * @param string $left  The left argument
     * @param string $right The right argument
     *
     * @return string The binary result
     */
    protected function addBinary($left, $right) {
        $len    = max(strlen($left), strlen($right));
        $left   = str_pad($left, $len, chr(0), STR_PAD_LEFT);
        $right  = str_pad($right, $len, chr(0), STR_PAD_LEFT);
        $result = '';
        $carry  = 0;
        for ($i = 0; $i < $len; $i++) {
            $sum     = ord($left[$len - $i - 1])
                 + ord($right[$len - $i - 1])
                 + $carry;
            $result .= chr($sum % 256);
            $carry   = $sum >> 8;
        }
        while ($carry) {
            $result .= chr($carry % 256);
            $carry >>= 8;
        }
        return strrev($result);
    }

    /**
     * Subtract two binary strings using 256's compliment
     *
     * @param string $left  The left argument
     * @param string $right The right argument
     *
     * @return string The binary result
     */
    protected function subtractBinary($left, $right) {
        $len    = max(strlen($left), strlen($right));
        $left   = str_pad($left, $len, chr(0), STR_PAD_LEFT);
        $right  = str_pad($right, $len, chr(0), STR_PAD_LEFT);
        $right  = $this->compliment($right);
        $result = $this->addBinary($left, $right);
        if (strlen($result) > $len) {
            // Positive Result
            $carry  = substr($result, 0, -1 * $len);
            $result = substr($result, strlen($carry));
            return array(
                '',
                $this->addBinary($result, $carry)
            );
        }
        return array('-', $this->compliment($result));
    }

    /**
     * Take the 256 base compliment
     *
     * @param string $string The binary string to compliment
     *
     * @return string The complimented string
     */
    protected function compliment($string) {
        $result = '';
        $len    = strlen($string);
        for ($i = 0; $i < $len; $i++) {
            $result .= chr(255 - ord($string[$i]));
        }
        return $result;
    }

    /**
     * Transform a string number into a binary string using base autodetection
     *
     * @param string $string The string to transform
     *
     * @return string The binary transformed number
     */
    protected function normalize($string) {
        return BaseConverter::convertToBinary(
            $string,
            '0123456789'
        );
    }

}