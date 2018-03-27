<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @see       http://github.com/zendframework/zend-stratigility for the canonical source repository
 * @copyright Copyright (c) 2015-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-stratigility/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Stratigility;

use Interop\Http\Middleware\MiddlewareInterface as InteropMiddlewareInterface;
use Interop\Http\Middleware\ServerMiddlewareInterface;
use InvalidArgumentException;
use OutOfRangeException;

/**
 * Value object representing route-based middleware
 *
 * Details the subpath on which the middleware is active, and the
 * handler for the middleware itself.
 *
 * @property-read callable $handler Handler for this route
 * @property-read string $path Path for this route
 */
class Route
{
    /**
     * @var callable
     */
    protected $handler;

    /**
     * @var string
     */
    protected $path;

    /**
     * @param string $path
     * @param callable|InteropMiddlewareInterface|ServerMiddlewareInterface $handler
     * @throws Exception\InvalidArgumentException if the $handler provided is
     *     neither a callable nor an http-interop implementation.
     */
    public function __construct($path, $handler)
    {
        if (! is_string($path)) {
            throw new InvalidArgumentException('Path must be a string');
        }

        if (! (is_callable($handler)
            || $handler instanceof ServerMiddlewareInterface
            || $handler instanceof InteropMiddlewareInterface
        )) {
            throw new Exception\InvalidMiddlewareException(sprintf(
                'Middleware must be callable or implement an http-interop middleware interface; received %s',
                is_object($handler) ? get_class($handler) : gettype($handler)
            ));
        }

        $this->path    = $path;
        $this->handler = $handler;
    }

    /**
     * @param mixed $name
     * @return mixed
     * @throws OutOfRangeException for invalid properties
     */
    public function __get($name)
    {
        if (! property_exists($this, $name)) {
            throw new OutOfRangeException('Only the path and handler may be accessed from a Route instance');
        }
        return $this->{$name};
    }
}
