<?php

/**
 * This does nothing if the libsodium extension is loaded, so it's harmless.
 *
 * This file alone is released under CC0 and WTFPL dual licensing.
 */
namespace Sodium {
    if (!extension_loaded('libsodium')) {

        /**
         * Generate a string of random bytes
         * /dev/urandom
         *
         * @param int $length
         * @return string
         */
        function randombytes_buf(
            $length
        )
        {
            return '';
        }

        /**
         * Generate a 16-bit integer
         * /dev/urandom
         *
         * @return int
         */
        function randombytes_random16()
        {
            return '';
        }

        /**
         * Generate an unbiased random integer between 0 and a specified value
         * /dev/urandom
         *
         * @param int $upperBoundNonInclusive
         * @return int
         */
        function randombytes_uniform(
            $upperBoundNonInclusive
        )
        {
            return 0;
        }
    }
}
namespace {
    class Sodium
    {

        /**
         * Generate a string of random bytes
         * /dev/urandom
         *
         * @param int $length
         * @return string
         */
        public static function randombytes_buf($length)
        {
            return '';
        }

        /**
         * Generate a 16-bit integer
         * /dev/urandom
         *
         * @return int
         */
        public static function randombytes_random16()
        {
            return '';
        }

        /**
         * Generate an unbiased random integer between 0 and a specified value
         * /dev/urandom
         *
         * @param int $upperBoundNonInclusive
         * @return int
         */
        public static function randombytes_uniform($upperBoundNonInclusive = 0)
        {
            return 0;
        }
    }
}