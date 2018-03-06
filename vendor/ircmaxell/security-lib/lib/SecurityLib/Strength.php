<?php
/**
 * The strength FlyweightEnum class
 *
 * PHP version 5.3
 *
 * @category   PHPPasswordLib
 * @package    Core
 * @author     Anthony Ferrara <ircmaxell@ircmaxell.com>
 * @copyright  2011 The Authors
 * @license    http://www.opensource.org/licenses/mit-license.html  MIT License
 * @version    Build @@version@@
 */

namespace SecurityLib;

/**
 * The strength FlyweightEnum class
 *
 * All mixing strategies must extend this class
 *
 * @category   PHPPasswordLib
 * @package    Core
 * @author     Anthony Ferrara <ircmaxell@ircmaxell.com>
 */
class Strength extends Enum {

    /**
     * We provide a default value of VeryLow so that we don't accidentally over
     * state the strength if we forget to pass in a value...
     */
    const __DEFAULT = self::VERYLOW;

    /**
     * This represents Non-Cryptographic strengths.  It should not be used any time
     * that security or confidentiality is at stake
     */
    const VERYLOW = 1;

    /**
     * This represents the bottom line of Cryptographic strengths.  It may be used
     * for low security uses where some strength is required.
     */
    const LOW = 3;

    /**
     * This is the general purpose Cryptographical strength.  It should be suitable
     * for all uses except the most sensitive.
     */
    const MEDIUM = 5;

    /**
     * This is the highest strength available.  It should not be used unless the
     * high strength is needed, due to hardware constraints (and entropy
     * limitations).
     */
    const HIGH = 7;

}
