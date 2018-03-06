<?php
/**
 * The interface that all hash implementations must implement
 *
 * PHP version 5.3
 *
 * @category   PHPPasswordLib
 * @package    Hash
 * @author     Anthony Ferrara <ircmaxell@ircmaxell.com>
 * @copyright  2011 The Authors
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @license    http://www.gnu.org/licenses/lgpl-2.1.html LGPL v 2.1
 */

namespace PasswordLibTest\Mocks;

/**
 * The interface that all hash implementations must implement
 *
 * @category   PHPPasswordLib
 * @package    Hash
 * @author     Anthony Ferrara <ircmaxell@ircmaxell.com>
 */
class AbstractMock {

    protected $callbacks = array();

    public static function init() {}

    public function __construct(array $callbacks = array()) {
        $this->callbacks = $callbacks;
    }

    public function __call($name, array $args = array()) {
        if (isset($this->callbacks[$name])) {
            return call_user_func_array($this->callbacks[$name], $args);
        }
        return null;
    }

}
