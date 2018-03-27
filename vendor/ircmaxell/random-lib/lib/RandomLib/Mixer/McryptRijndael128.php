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
 * mcrypt mixer using the Rijndael cipher with 128 bit block size
 *
 * PHP version 5.3
 *
 * @category   PHPCryptLib
 * @package    Random
 * @subpackage Mixer
 *
 * @author     Anthony Ferrara <ircmaxell@ircmaxell.com>
 * @copyright  2013 The Authors
 * @license    http://www.opensource.org/licenses/mit-license.html  MIT License
 *
 * @version    Build @@version@@
 */
namespace RandomLib\Mixer;

use RandomLib\AbstractMcryptMixer;
use SecurityLib\Strength;

/**
 * mcrypt mixer using the Rijndael cipher with 128 bit block size
 *
 * @category   PHPCryptLib
 * @package    Random
 * @subpackage Mixer
 *
 * @author     Anthony Ferrara <ircmaxell@ircmaxell.com>
 * @author     Chris Smith <chris@cs278.org>
 */
class McryptRijndael128 extends AbstractMcryptMixer
{
    /**
     * {@inheritdoc}
     */
    public static function getStrength()
    {
        return new Strength(Strength::HIGH);
    }

    /**
     * {@inheritdoc}
     */
    protected function getCipher()
    {
        return 'rijndael-128';
    }
}
