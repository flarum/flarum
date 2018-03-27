<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @see       http://github.com/zendframework/zend-stratigility for the canonical source repository
 * @copyright Copyright (c) 2015-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-stratigility/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Stratigility;

use Interop\Http\Middleware\ServerMiddlewareInterface;
use Throwable;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Dispatch middleware
 *
 * This class is an implementation detail of Next.
 *
 * @internal
 * @deprecated since 1.3.0; to be removed in 2.0.0.
 */
class Dispatch
{
    /**
     * Flag indicating whether or not to raise throwables during dispatch; when
     * false, a try/catch block is used instead (default behavior).
     *
     * @var bool
     */
    private $raiseThrowables = false;

    /**
     * @var ResponseInterface
     */
    private $responsePrototype;

    /**
     * Dispatch middleware
     *
     * Given a route (which contains the handler for given middleware),
     * the $err value passed to $next, $next, and the request and response
     * objects, dispatch a middleware handler.
     *
     * If $err is non-falsy, and the current handler has an arity of 4,
     * it will be dispatched.
     *
     * If $err is falsy, and the current handler has an arity of < 4,
     * it will be dispatched.
     *
     * In all other cases, the handler will be ignored, and $next will be
     * invoked with the current $err value.
     *
     * If an exception is raised when executing the handler, the exception
     * will be assigned as the value of $err, and $next will be invoked
     * with it.
     *
     * @param Route $route
     * @param mixed $err
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param callable $next
     * @return ResponseInterface
     */
    public function __invoke(
        Route $route,
        $err,
        ServerRequestInterface $request,
        ResponseInterface $response,
        callable $next
    ) {
        if (! $this->responsePrototype) {
            $this->setResponsePrototype($response);
        }

        // Handle middleware pipes as callables if an $err is present
        if ($route->handler instanceof MiddlewarePipe && null !== $err) {
            return $this->dispatchCallableMiddleware($route->handler, $next, $request, $response, $err);
        }

        if ($route->handler instanceof ServerMiddlewareInterface) {
            return $this->dispatchInteropMiddleware($route->handler, $next, $request);
        }

        return $this->dispatchCallableMiddleware($route->handler, $next, $request, $response, $err);
    }

    /**
     * Process middleware as invoked from an http-interop middleware instance.
     *
     * Name chosen to mirror Interop\Http\Middleware\DelegateInterface, and thus
     * imply this should be dispatched from interop middleware.
     *
     * If the route provided is not http-interop middleware, this method will
     * dispatch using callable middleware semantics; otherwise, it dispatches
     * using http-interop semantics.
     *
     * @param Route $route
     * @param RequestInterface $request
     * @param callable $next
     * @return ResponseInterface
     * @throws Exception\MissingResponsePrototypeException
     * @throws Exception\InvalidRequestTypeException
     */
    public function process(Route $route, RequestInterface $request, callable $next)
    {
        if ($this->isNotInteropMiddleware($route->handler, $request)) {
            return $this->dispatchCallableMiddleware($route->handler, $next, $request, $this->responsePrototype);
        }

        return $this->dispatchInteropMiddleware($route->handler, $next, $request);
    }

    /**
     * Enables the "raise throwables", causing this instance to raise
     * throwables instead of catch them.
     *
     * @return void
     */
    public function raiseThrowables()
    {
        $this->raiseThrowables = true;
    }

    /**
     * Set a response prototype to use when invoking callable middleware following http-interop middleware.
     *
     * @param ResponseInterface $responsePrototype
     * @return void
     */
    public function setResponsePrototype(ResponseInterface $responsePrototype)
    {
        $this->responsePrototype = $responsePrototype;
    }

    /**
     * Test if the middleware composed by a route is not http-interop middleware.
     *
     * @param mixed $handler
     * @param RequestInterface $request
     * @return bool
     * @throws Exception\MissingResponsePrototypeException if non-interop
     *     middleware is detected, but no response prototype is available.
     * @throws Exception\InvalidRequestTypeException if non-interop middleware
     *     is detected, but the request provided is not a server-side request.
     */
    private function isNotInteropMiddleware($handler, RequestInterface $request)
    {
        if ($handler instanceof ServerMiddlewareInterface) {
            return false;
        }

        if (! $this->responsePrototype) {
            throw new Exception\MissingResponsePrototypeException(
                'Invoking callable middleware following http-interop middleware, '
                . 'but no response prototype is present; please inject one in your '
                . 'MiddlewarePipe or ensure Stratigility callable middleware exists '
                . 'in the outer layer of your application.'
            );
        }

        if (! $request instanceof ServerRequestInterface) {
            throw new Exception\InvalidRequestTypeException(
                'Invoking callable middleware following http-interop middleware, '
                . 'but a server request was not provided.'
            );
        }

        return true;
    }

    /**
     * Dispatch non-interop middleware.
     *
     * @param callable $middleware
     * @param callable $next
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param mixed $err
     * @return ResponseInterface
     */
    private function dispatchCallableMiddleware(
        callable $middleware,
        callable $next,
        ServerRequestInterface $request,
        ResponseInterface $response,
        $err = null
    ) {
        $hasError = (null !== $err);

        switch (true) {
            case ($middleware instanceof ErrorMiddlewareInterface):
                $arity = 4;
                break;
            case ($middleware instanceof MiddlewareInterface):
                $arity = 3;
                break;
            default:
                $arity = Utils::getArity($middleware);
                break;
        }

        if ($this->raiseThrowables) {
            if ($hasError && $arity === 4) {
                return $middleware($err, $request, $response, $next);
            }

            if (! $hasError && $arity < 4) {
                return $middleware($request, $response, $next);
            }

            return $next($request, $response, $err);
        }

        try {
            if ($hasError && $arity === 4) {
                return $middleware($err, $request, $response, $next);
            }

            if (! $hasError && $arity < 4) {
                return $middleware($request, $response, $next);
            }
        } catch (Throwable $throwable) {
            return $next($request, $response, $throwable);
        } catch (\Exception $exception) {
            return $next($request, $response, $exception);
        }

        return $next($request, $response, $err);
    }

    /**
     * Dispatch http-interop middleware
     *
     * @param ServerMiddlewareInterface $middleware
     * @param callable $next
     * @param RequestInterface $request
     * @return ResponseInterface
     * @throws Exception\MissingResponsePrototypeException if no response
     *     prototype is available with which to call the delegate.
     * @throws Exception\InvalidRequestTypeException if the request provided
     *     is not a server-side request.
     */
    private function dispatchInteropMiddleware(
        ServerMiddlewareInterface $middleware,
        callable $next,
        RequestInterface $request
    ) {
        if ($middleware instanceof MiddlewarePipe
            && ! $middleware->hasResponsePrototype()
            && $this->responsePrototype
        ) {
            $middleware->setResponsePrototype($this->responsePrototype);
        }

        if ($this->raiseThrowables) {
            return $middleware->process($request, $next);
        }

        try {
            return $middleware->process($request, $next);
        } catch (Throwable $throwable) {
            return $this->handleThrowableFromInteropMiddleware($throwable, $request, $next);
        } catch (\Exception $exception) {
            return $this->handleThrowableFromInteropMiddleware($exception, $request, $next);
        }
    }

    /**
     * @param Throwable|\Exception $throwable
     * @param RequestInterface $request
     * @param callable $next
     * @return ResponseInterface
     * @throws Exception\MissingResponsePrototypeException if no response
     *     prototype is available with which to call the delegate.
     * @throws Exception\InvalidRequestTypeException if the request provided
     *     is not a server-side request.
     */
    private function handleThrowableFromInteropMiddleware(
        $throwable,
        RequestInterface $request,
        callable $next
    ) {
        if (! $this->responsePrototype) {
            throw new Exception\MissingResponsePrototypeException(
                'Caught Throwable from http-interop middleware, but unable to handle '
                . 'due to missing response prototype',
                $throwable->getCode(),
                $throwable
            );
        }

        if (! $request instanceof ServerRequestInterface) {
            throw new Exception\InvalidRequestTypeException(
                'Caught Throwable from http-interop middleware, but unable to handle '
                . 'because request is not a server request',
                $throwable->getCode(),
                $throwable
            );
        }

        return $next($request, $this->responsePrototype, $throwable);
    }
}
