<?php
/**
 * The base abstract factory used by all PasswordLib factories
 *
 * PHP version 5.3
 *
 * @category  PHPPasswordLib
 * @package   Core
 * @author    Anthony Ferrara <ircmaxell@ircmaxell.com>
 * @copyright 2011 The Authors
 * @license    http://www.opensource.org/licenses/mit-license.html  MIT License
 * @version   Build @@version@@
 */

namespace SecurityLib;

/**
 * The base abstract factory used by all PasswordLib factories
 *
 * @category PHPPasswordLib
 * @package  Core
 * @author   Anthony Ferrara <ircmaxell@ircmaxell.com>
 */
abstract class AbstractFactory {

    /**
     * Register a type with the factory by name
     *
     * This is an internal method to check if a provided class name implements
     * an interface, and if it does to append that class to an internal array
     * by name.
     *
     * @param string  $type        The name of the variable to store the class
     * @param string  $implements  The interface to validate against
     * @param string  $name        The name of this particular class
     * @param string  $class       The fully qualified class name
     * @param boolean $instantiate Should the class be stored instantiated
     *
     * @return void
     * @throws InvalidArgumentException If class does not implement interface
     */
    protected function registerType(
        $type,
        $implements,
        $name,
        $class,
        $instantiate = false
    ) {
        $name = strtolower($name);
        $refl = new \ReflectionClass($class);
        if (!$refl->implementsInterface($implements)) {
            $message = sprintf('Class must implement %s', $implements);
            throw new \InvalidArgumentException($message);
        }
        if ($instantiate) {
            $class = new $class;
        }

        $this->{$type}[$name] = $class;
    }

    /**
     * Load a set of classes from a directory into the factory
     *
     * @param string $directory The directory to search for classes in
     * @param string $namespace The namespace prefix for any found classes
     * @param string $callback  The callback with which to register the class
     *
     * @return void
     */
    protected function loadFiles($directory, $namespace, $callback) {
        foreach (new \DirectoryIterator($directory) as $file) {
            $filename = $file->getBasename();
            if ($file->isFile() && substr($filename, -4) == '.php') {
                $name  = substr($filename, 0, -4);
                $class = $namespace . $name;
                call_user_func($callback, $name, $class);
            }
        }
    }

}
