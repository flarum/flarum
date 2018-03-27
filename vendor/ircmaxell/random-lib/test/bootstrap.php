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
 * Bootstrap the library.  This registers a simple autoloader for autoloading
 * classes
 *
 * If you are using this library inside of another that uses a similar
 * autoloading system, you can use that autoloader instead of this file.
 *
 * PHP version 5.3
 *
 * @category   PHPPasswordLib
 * @package    test
 *
 * @author     Anthony Ferrara <ircmaxell@ircmaxell.com>
 * @copyright  2011 The Authors
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @license    http://www.gnu.org/licenses/lgpl-2.1.html LGPL v 2.1
 */
namespace RandomLibTest;

ini_set('memory_limit', '1G');

/**
 * The simple autoloader for the PasswordLibTest libraries.
 *
 * This does not use the PRS-0 standards due to the namespace prefix and directory
 * structure
 *
 * @param string $class The class name to load
 *
 * @return void
 */
spl_autoload_register(function ($class) {
    $nslen = strlen(__NAMESPACE__);
    if (substr($class, 0, $nslen) != __NAMESPACE__) {
        //Only autoload libraries from this package
        return;
    }
    $path = substr(str_replace('\\', '/', $class), $nslen);
    $path = __DIR__ . $path . '.php';
    if (file_exists($path)) {
        require $path;
    }
});

define('PATH_ROOT', dirname(__DIR__));

require_once dirname(__DIR__) . '/vendor/autoload.php';
