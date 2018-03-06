<?php
/**
 * A class for arbitrary precision math functions implemented using bcmath
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
 * A class for arbitrary precision math functions implemented using bcmath
 *
 * @category   PHPPasswordLib
 * @package    Core
 * @subpackage BigMath
 */
class BCMath extends \SecurityLib\BigMath {

    /**
     * Add two numbers together
     * 
     * @param string $left  The left argument
     * @param string $right The right argument
     * 
     * @return A base-10 string of the sum of the two arguments
     */
    public function add($left, $right) {
        return bcadd($left, $right, 0);
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
        return bcsub($left, $right);
    }

}