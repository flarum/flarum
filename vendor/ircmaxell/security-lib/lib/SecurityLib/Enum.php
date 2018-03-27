<?php
/**
 * The Enum base class for Enum functionality
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

use \ReflectionObject;

/**
 * The Enum base class for Enum functionality
 *
 * This is based off of the SplEnum class implementation (which is only available
 * as a PECL extension in 5.3)
 *
 * @see        http://www.php.net/manual/en/class.splenum.php
 * @category   PHPPasswordLib
 * @package    Core
 * @author     Anthony Ferrara <ircmaxell@ircmaxell.com>
 */
abstract class Enum {

    /**
     * A default value of null is provided.  Override this to set your own default
     */
    const __DEFAULT = null;

    /**
     * @var string The name of the constant this instance is using
     */
    protected $name = '';

    /**
     * @var scalar The value of the constant this instance is using.
     */
    protected $value = '';

    /**
     * Creates a new value of the Enum type
     *
     * @param mixed   $value  The value this instance represents
     * @param boolean $strict Not Implemented at this time
     *
     * @return void
     * @throws UnexpectedValueException If the value is not a constant
     */
    public function __construct($value = null, $strict = false) {
        if (is_null($value)) {
            $value = static::__DEFAULT;
        }
        $validValues = $this->getConstList();
        $this->name  = array_search($value, $validValues);
        if (!$this->name) {
            throw new \UnexpectedValueException(
                'Value not a const in enum ' . get_class($this)
            );
        }
        $this->value = $value;
    }

    /**
     * Cast the current object to a string and return its value
     *
     * @return mixed the current value of the instance
     */
    public function __toString() {
        return (string) $this->value;
    }

    /**
     * Compare two enums using numeric comparison
     *
     * @param Enum $arg The enum to compare this instance to
     *
     * @return int 0 if same, 1 if the argument is greater, -1 else
     */
    public function compare(Enum $arg) {
        if ($this->value == $arg->value) {
            return 0;
        } elseif ($this->value > $arg->value) {
            return -1;
        } else {
            return 1;
        }
    }

    /**
     * Returns all constants (including values) as an associative array
     *
     * @param boolean $include_default Include the __default magic value?
     *
     * @return array All of the constants found against this instance
     */
    public function getConstList($include_default = false) {
        static $constCache = array();
        $class             = get_class($this);
        if (!isset($constCache[$class])) {
            $reflector          = new ReflectionObject($this);
            $constCache[$class] = $reflector->getConstants();
        }
        if (!$include_default) {
            $constants = $constCache[$class];
            unset($constants['__DEFAULT']);
            return $constants;
        }
        return $constCache[$class];
    }

}