<?php

/*
 * The RandomLib library for securely generating random numbers and strings in PHP
 *
 * @author     Anthony Ferrara <ircmaxell@ircmaxell.com>
 * @copyright  2011 The Authors
 * @license    http://www.opensource.org/licenses/mit-license.html  MIT License
 * @version    Build @@version@@
 */

/**
 * The Microtime Random Number Source
 *
 * This uses the current micro-second (looped several times) for a **very** weak
 * random number source.  This is only useful when combined with several other
 * stronger sources
 *
 * PHP version 5.3
 *
 * @category   PHPCryptLib
 * @package    Random
 * @subpackage Source
 *
 * @author     Anthony Ferrara <ircmaxell@ircmaxell.com>
 * @copyright  2011 The Authors
 * @license    http://www.opensource.org/licenses/mit-license.html  MIT License
 *
 * @version    Build @@version@@
 */
namespace RandomLib\Source;

use SecurityLib\Strength;
use SecurityLib\Util;

/**
 * The Microtime Random Number Source
 *
 * This uses the current micro-second (looped several times) for a **very** weak
 * random number source.  This is only useful when combined with several other
 * stronger sources
 *
 * @category   PHPCryptLib
 * @package    Random
 * @subpackage Source
 *
 * @author     Anthony Ferrara <ircmaxell@ircmaxell.com>
 * @codeCoverageIgnore
 */
final class MicroTime extends \RandomLib\AbstractSource
{

    /**
     * A static counter to ensure unique hashes and prevent state collisions
     *
     * @var int A counter
     */
    private static $counter = null;

    /**
     * The current state of the random number generator.
     *
     * @var string The state of the PRNG
     */
    private static $state = '';

    public function __construct()
    {
        $state = self::$state;
        if (function_exists('posix_times')) {
            $state .= serialize(posix_times());
        }
        if (!defined('HHVM_VERSION') && function_exists('zend_thread_id')) {
            $state .= zend_thread_id();
        }
        if (function_exists('hphp_get_thread_id')) {
            $state .= hphp_get_thread_id();
        }
        $state      .= getmypid() . memory_get_usage();
        $state      .= serialize($_ENV);
        $state      .= serialize($_SERVER);
        $state      .= count(debug_backtrace(false));
        self::$state = hash('sha512', $state, true);
        if (is_null(self::$counter)) {
            list(, self::$counter) = unpack("i", Util::safeSubstr(self::$state, 0, 4));
            $seed = $this->generate(Util::safeStrlen(dechex(PHP_INT_MAX)));
            list(, self::$counter) = unpack("i", $seed);
        }
    }

    /**
     * Generate a random string of the specified size
     *
     * @param int $size The size of the requested random string
     *
     * @return string A string of the requested size
     */
    public function generate($size)
    {
        $result      = '';
        $seed        = microtime() . memory_get_usage();
        self::$state = hash('sha512', self::$state . $seed, true);
        /**
         * Make the generated randomness a bit better by forcing a GC run which
         * should complete in a indeterminate amount of time, hence improving
         * the strength of the randomness a bit. It's still not crypto-safe,
         * but at least it's more difficult to predict.
         */
        gc_collect_cycles();
        for ($i = 0; $i < $size; $i += 8) {
            $seed = self::$state .
                    microtime() .
                    pack('Ni', $i, self::counter());
            self::$state = hash('sha512', $seed, true);
            /**
             * We only use the first 8 bytes here to prevent exposing the state
             * in its entirety, which could potentially expose other random
             * generations in the future (in the same process)...
             */
            $result .= Util::safeSubstr(self::$state, 0, 8);
        }

        return Util::safeSubstr($result, 0, $size);
    }

    private static function counter()
    {
        if (self::$counter >= PHP_INT_MAX) {
            self::$counter = -1 * PHP_INT_MAX - 1;
        } else {
            self::$counter++;
        }

        return self::$counter;
    }
}
