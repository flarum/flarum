<?php
/**
 * A class for arbitrary precision math functions implemented using GMP
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

/**
 * A class for arbitrary precision math functions implemented using GMP
 *
 * @category   PHPPasswordLib
 * @package    Core
 * @subpackage BigMath
 */
class GMP extends \SecurityLib\BigMath {

    /**
     * Add two numbers together
     * 
     * @param string $left  The left argument
     * @param string $right The right argument
     * 
     * @return A base-10 string of the sum of the two arguments
     */
    public function add($left, $right) {
        return gmp_strval(gmp_add($left, $right));
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
        return gmp_strval(gmp_sub($left, $right));
    }

}