<?php
/**
 * @link      http://github.com/zendframework/zend-stratigility for the canonical source repository
 * @copyright Copyright (c) 2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Stratigility\Middleware;

use Interop\Http\Middleware\DelegateInterface;
use Interop\Http\Middleware\ServerMiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Zend\Stratigility\Delegate\CallableDelegateDecorator;

class NotFoundHandler implements ServerMiddlewareInterface
{
    /**
     * @var ResponseInterface
     */
    private $responsePrototype;

    /**
     * @param ResponseInterface $responsePrototype Empty/prototype response to
     *     update and return when returning an 404 response.
     */
    public function __construct(ResponseInterface $responsePrototype)
    {
        $this->responsePrototype = $responsePrototype;
    }

    /**
     * Proxy to process()
     *
     * Proxies to process, after first wrapping the `$next` argument using the
     * CallableDelegateDecorator.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param callable $next
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        return $this->process($request, new CallableDelegateDecorator($next, $response));
    }

    /**
     * Creates and returns a 404 response.
     *
     * @param ServerRequestInterface $request Ignored.
     * @param DelegateInterface $delegate Ignored.
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $response = $this->responsePrototype
            ->withStatus(404);
        $response->getBody()->write(sprintf(
            "Cannot %s %s",
            $request->getMethod(),
            (string) $request->getUri()
        ));
        return $response;
    }
}
