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
 * The Mixer strategy interface.
 *
 * All mixing strategies must implement this interface
 *
 * PHP version 5.3
 *
 * @category   PHPPasswordLib
 * @package    Random
 *
 * @author     Anthony Ferrara <ircmaxell@ircmaxell.com>
 * @copyright  2011 The Authors
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @license    http://www.gnu.org/licenses/lgpl-2.1.html LGPL v 2.1
 */
namespace RandomLibtest\Mocks\Random;

/**
 * The Mixer strategy interface.
 *
 * All mixing strategies must implement this interface
 *
 * @category   PHPPasswordLib
 * @package    Random
 *
 * @author     Anthony Ferrara <ircmaxell@ircmaxell.com>
 */
class Generator extends \RandomLib\Generator
{
    protected $callbacks = array();

    public static function init()
    {
    }

    public function __construct(array $callbacks = array())
    {
        $this->callbacks = $callbacks;
    }

    public function __call($name, array $args = array())
    {
        if (isset($this->callbacks[$name])) {
            return call_user_func_array($this->callbacks[$name], $args);
        }

        return null;
    }

    public function addSource(\PasswordLib\Random\Source $source)
    {
        return $this->__call('addSource', array($source));
    }

    public function generate($size)
    {
        return $this->__call('generate', array($size));
    }

    public function generateInt($min = 0, $max = \PHP_INT_MAX)
    {
        return $this->__call('generateInt', array($min, $max));
    }

    public function generateString($length, $chars = '')
    {
        return $this->__call('generateString', array($length, $chars));
    }
}
