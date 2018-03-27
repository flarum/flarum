<?php
/**
 * @link      http://github.com/zendframework/zend-stratigility for the canonical source repository
 * @copyright Copyright (c) 2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Stratigility\Exception;

use InvalidArgumentException;

/**
 * Exception to raise when $next argument is missing.
 */
class MissingDelegateException extends InvalidArgumentException
{
}
