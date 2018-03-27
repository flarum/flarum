<?php
/**
 * The interface that all hash implementations must implement
 *
 * PHP version 5.3
 *
 * @category   PHPSecurityLib
 * @package    Hash
 * @author     Anthony Ferrara <ircmaxell@ircmaxell.com>
 * @copyright  2011 The Authors
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @license    http://www.gnu.org/licenses/lgpl-2.1.html LGPL v 2.1
 */

namespace SecurityLibTest\Mocks;

/**
 * The interface that all hash implementations must implement
 *
 * @category   PHPSecurityLib
 * @package    Hash
 * @author     Anthony Ferrara <ircmaxell@ircmaxell.com>
 */
class Strength extends \SecurityLib\Strength {

    const MEDIUMLOW = 4;
    const SUPERHIGH = 999;

}
