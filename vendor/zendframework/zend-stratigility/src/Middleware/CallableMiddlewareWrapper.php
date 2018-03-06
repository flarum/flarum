<?php
/**
 * @link      http://github.com/zendframework/zend-stratigility for the canonical source repository
 * @copyright Copyright (c) 2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Stratigility\Middleware;

use Interop\Http\Middleware\DelegateInterface;
use Interop\Http\Middleware\ServerMiddlewareInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Stratigility\Next;

/**
 * Decorate legacy callable middleware to make it dispatchable as server
 * middleware.
 */
class CallableMiddlewareWrapper implements ServerMiddlewareInterface
{
    /**
     * @var callable
     */
    private $middleware;

    /**
     * @var ResponseInterface
     */
    private $responsePrototype;

    /**
     * @param callable $middleware
     * @param ResponseInterface $prototype
     */
    public function __construct(callable $middleware, ResponseInterface $prototype)
    {
        $this->middleware = $middleware;
        $this->responsePrototype = $prototype;
    }

    /**
     * Proxies to underlying middleware, using composed response prototype.
     *
     * Also decorates the $delegator using the CallableMiddlewareWrapper.
     *
     * {@inheritDocs}
     */
    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $middleware = $this->middleware;
        $delegate = $delegate instanceof Next
            ? $delegate
            : function ($request) use ($delegate) {
                return $delegate->process($request);
            };

        return $middleware($request, $this->responsePrototype, $delegate);
    }
}
