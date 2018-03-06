<?php
/**
 * A hash utility data mapper class
 * 
 * This class's purpose is to store information about hash algorithms that is
 * otherwise unavailable during runtime.  Some information is available (such 
 * as the output size), but is included anyway for performance and completeness
 * reasons.
 *
 * PHP version 5.3
 *
 * @category   PHPPasswordLib
 * @package    Hash
 * @author     Anthony Ferrara <ircmaxell@ircmaxell.com>
 * @copyright  2011 The Authors
 * @license    http://www.opensource.org/licenses/mit-license.html  MIT License
 * @version    Build @@version@@
 */
namespace SecurityLib;

/**
 * A hash utility data mapper class
 * 
 * This class's purpose is to store information about hash algorithms that is
 * otherwise unavailable during runtime.  Some information is available (such 
 * as the output size), but is included anyway for performance and completeness
 * reasons.
 *
 * PHP version 5.3
 *
 * @category   PHPPasswordLib
 * @package    Hash
 * @author     Anthony Ferrara <ircmaxell@ircmaxell.com>
 */
class Hash {

    /**
     * This array contains information about each hash function available to PHP
     * at the present time.  Block sizes are not available from functions, so they
     * must be hard coded.
     * 
     * The "secure" indicates the strength of the hash and whether or not any known
     * cryptographic attacks exist for the hash function. This will only apply when
     * using the hash functions for situations that require cryptographic strength
     * such as message signing.  For other uses the insecure ones can have valid
     * uses.
     * 
     * @var array An array of information about each supported hash function 
     */
    protected static $hashInfo = array(
        'md2' => array(
            'HashSize'  => 128,
            'BlockSize' => 128,
            'secure'    => false,
        ),
        'md4' => array(
            'HashSize'  => 128,
            'BlockSize' => 512,
            'secure'    => false,
        ),
        'md5' => array(
            'HashSize'  => 128,
            'BlockSize' => 512,
            'secure'    => false,
        ),
        'sha1' => array(
            'HashSize'  => 160,
            'BlockSize' => 512,
            'secure'    => false,
        ),
        'sha224' => array(
            'HashSize'  => 224,
            'BlockSize' => 512,
            'secure'    => true,
        ),
        'sha256' => array(
            'HashSize'  => 256,
            'BlockSize' => 512,
            'secure'    => true,
        ),
        'sha384' => array(
            'HashSize'  => 384,
            'BlockSize' => 1024,
            'secure'    => true,
        ),
        'sha512' => array(
            'HashSize'  => 512,
            'BlockSize' => 1024,
            'secure'    => true,
        ),
        'ripemd128' => array(
            'HashSize'  => 128,
            'BlockSize' => 512,
            'secure'    => true,
        ),
        'ripemd160' => array(
            'HashSize'  => 160,
            'BlockSize' => 512,
            'secure'    => true,
        ),
        'ripemd256' => array(
            'HashSize'  => 256,
            'BlockSize' => 512,
            'secure'    => true,
        ),
        'ripemd320' => array(
            'HashSize'  => 320,
            'BlockSize' => 512,
            'secure'    => true,
        ),
        'whirlpool' => array(
            'HashSize'  => 512,
            'BlockSize' => 512,
            'secure'    => true,
        ),
        'tiger128,3' => array(
            'HashSize'  => 128,
            'BlockSize' => 512,
            'secure'    => true,
        ),
        'tiger160,3' => array(
            'HashSize'  => 160,
            'BlockSize' => 512,
            'secure'    => true,
        ),
        'tiger192,3' => array(
            'HashSize'  => 192,
            'BlockSize' => 512,
            'secure'    => true,
        ),
        'tiger128,4' => array(
            'HashSize'  => 128,
            'BlockSize' => 512,
            'secure'    => true,
        ),
        'tiger160,4' => array(
            'HashSize'  => 160,
            'BlockSize' => 512,
            'secure'    => true,
        ),
        'tiger192,4' => array(
            'HashSize'  => 192,
            'BlockSize' => 512,
            'secure'    => true,
        ),
        'snefru' => array(
            'HashSize'  => 256,
            'BlockSize' => 512,
            'secure'    => false,
        ),
        'snefru256' => array(
            'HashSize'  => 256,
            'BlockSize' => 512,
            'secure'    => false,
        ),
        'gost' => array(
            'HashSize'  => 256,
            'BlockSize' => 256,
            'secure'    => false,
        ),
        'adler32' => array(
            'HashSize'  => 32,
            'BlockSize' => 16,
            'secure'    => false,
        ),
        'crc32' => array(
            'HashSize'  => 32,
            'BlockSize' => 32,
            'secure'    => false,
        ),
        'crc32b' => array(
            'HashSize'  => 32,
            'BlockSize' => 32,
            'secure'    => false,
        ),
        'salsa10' => array(
            'HashSize'  => 512,
            'BlockSize' => 512,
            'secure'    => true,
        ),
        'salsa20' => array(
            'HashSize'  => 512,
            'BlockSize' => 512,
            'secure'    => true,
        ),
        'haval128,3' => array(
            'HashSize'  => 128,
            'BlockSize' => 1024,
            'secure'    => false,
        ),
        'haval160,3' => array(
            'HashSize'  => 160,
            'BlockSize' => 1024,
            'secure'    => false,
        ),
        'haval192,3' => array(
            'HashSize'  => 192,
            'BlockSize' => 1024,
            'secure'    => false,
        ),
        'haval224,3' => array(
            'HashSize'  => 224,
            'BlockSize' => 1024,
            'secure'    => false,
        ),
        'haval256,3' => array(
            'HashSize'  => 256,
            'BlockSize' => 1024,
            'secure'    => false,
        ),
        'haval128,4' => array(
            'HashSize'  => 128,
            'BlockSize' => 1024,
            'secure'    => false,
        ),
        'haval160,4' => array(
            'HashSize'  => 160,
            'BlockSize' => 1024,
            'secure'    => false,
        ),
        'haval192,4' => array(
            'HashSize'  => 192,
            'BlockSize' => 1024,
            'secure'    => false,
        ),
        'haval224,4' => array(
            'HashSize'  => 224,
            'BlockSize' => 1024,
            'secure'    => false,
        ),
        'haval256,4' => array(
            'HashSize'  => 256,
            'BlockSize' => 1024,
            'secure'    => false,
        ),
        'haval128,5' => array(
            'HashSize'  => 128,
            'BlockSize' => 1024,
            'secure'    => false,
        ),
        'haval160,5' => array(
            'HashSize'  => 160,
            'BlockSize' => 1024,
            'secure'    => false,
        ),
        'haval192,5' => array(
            'HashSize'  => 192,
            'BlockSize' => 1024,
            'secure'    => false,
        ),
        'haval224,5' => array(
            'HashSize'  => 224,
            'BlockSize' => 1024,
            'secure'    => false,
        ),
        'haval256,5' => array(
            'HashSize'  => 256,
            'BlockSize' => 1024,
            'secure'    => false,
        ),
        'joaat' => array(
            'HashSize'  => 32,
            'BlockSize' => 64,
            'secure'    => false,
        ),
        'fnv132' => array(
            'HashSize'  => 32,
            'BlockSize' => 32,
            'secure'    => false,
        ),
        'fnv164' => array(
            'HashSize'  => 64,
            'BlockSize' => 64,
            'secure'    => false,
        ),
    );

    /**
     * Get the block size of the specified function in bytes
     *
     * @param string $hash The hash function to look up
     * 
     * @return int The number of bytes in the block function
     */
    public static function getBlockSize($hash) {
        return static::getBlockSizeInBits($hash) / 8;
    }

    /**
     * Get the block size of the specified function in bits
     *
     * @param string $hash The hash function to look up
     * 
     * @return int The number of bits in the block function
     */
    public static function getBlockSizeInBits($hash) {
        if (isset(static::$hashInfo[$hash]['BlockSize'])) {
            return static::$hashInfo[$hash]['BlockSize'];
        }
        return 0;
    }

    /**
     * Get the output size of the specified function in bytes
     *
     * @param string $hash The hash function to look up
     * 
     * @return int The number of bytes outputted by the hash function
     */
    public static function getHashSize($hash) {
        return static::getHashSizeInBits($hash) / 8;
    }

    /**
     * Get the output size of the specified function in bits
     *
     * @param string $hash The hash function to look up
     * 
     * @return int The number of bits outputted by the hash function
     */
    public static function getHashSizeInBits($hash) {
        if (isset(static::$hashInfo[$hash]['HashSize'])) {
            return static::$hashInfo[$hash]['HashSize'];
        }
        return 0;
    }

    /**
     * Check to see if the hash function specified is available
     *
     * @param string $hash The hash function to look up
     * 
     * @return boolean If the hash function is available in this version of PHP
     */
    public static function isAvailable($hash) {
        return in_array($hash, hash_algos());
    }

    /**
     * Check to see if the specified hash function is secure enough for 
     * cryptographic uses
     * 
     * The "secure" indicates the strength of the hash and whether or not any known
     * cryptographic attacks exist for the hash function. This will only apply when
     * using the hash functions for situations that require cryptographic strength
     * such as message signing.  For other uses the insecure ones can have valid
     * uses.
     * 
     * @param string $hash The hash function to look up
     * 
     * @return bolean If the function is secure
     */
    public static function isSecure($hash) {
        if (isset(static::$hashInfo[$hash])) {
            return static::$hashInfo[$hash]['secure'];
        }
        return false;
    }

}