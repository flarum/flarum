<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @see       http://github.com/zendframework/zend-stratigility for the canonical source repository
 * @copyright Copyright (c) 2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-stratigility/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Stratigility\Exception;

use InvalidArgumentException;

class InvalidMiddlewareException extends InvalidArgumentException
{
    /**
     * Create and return an InvalidArgumentException detailing the invalid middleware type.
     *
     * @param mixed $value
     * @return InvalidArgumentException
     */
    public static function fromValue($value)
    {
        $received = gettype($value);

        if (is_object($value)) {
            $received = get_class($value);
        }

        return new self(
            sprintf(
                'Middleware must be callable, %s found',
                $received
            )
        );
    }
}
