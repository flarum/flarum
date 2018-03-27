<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @see       http://github.com/zendframework/zend-stratigility for the canonical source repository
 * @copyright Copyright (c) 2015-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-stratigility/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Stratigility;

use Closure;
use Interop\Http\Middleware\DelegateInterface;
use Interop\Http\Middleware\ServerMiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use ReflectionFunction;
use ReflectionMethod;
use SplQueue;
use Zend\Stratigility\Exception\InvalidMiddlewareException;

/**
 * Pipe middleware like unix pipes.
 *
 * This class implements a pipeline of middleware, which can be attached using
 * the `pipe()` method, and is itself middleware.
 *
 * The request and response objects are decorated using the Zend\Stratigility\Http
 * variants in this package, ensuring that the request may store arbitrary
 * properties, and the response exposes the convenience `write()`, `end()`, and
 * `isComplete()` methods.
 *
 * It creates an instance of `Next` internally, invoking it with the provided
 * request and response instances; if no `$out` argument is provided, it will
 * create a `FinalHandler` instance and pass that to `Next` as well.
 *
 * Inspired by Sencha Connect.
 *
 * @see https://github.com/sencha/connect
 */
class MiddlewarePipe implements MiddlewareInterface, ServerMiddlewareInterface
{
    /**
     * @var SplQueue
     */
    protected $pipeline;

    /**
     * Whether or not exceptions thrown by middleware or invocation of
     * middleware using the $err argument should bubble up as exceptions.
     *
     * @var bool
     */
    private $raiseThrowables = false;

    /**
     * @var Response
     */
    protected $responsePrototype;

    /**
     * Constructor
     *
     * Initializes the queue.
     */
    public function __construct()
    {
        $this->pipeline = new SplQueue();
    }

    /**
     * Handle a request
     *
     * Takes the pipeline, creates a Next handler, and delegates to the
     * Next handler.
     *
     * If $out is a callable, it is used as the "final handler" when
     * $next has exhausted the pipeline; otherwise, a FinalHandler instance
     * is created and passed to $next during initialization.
     *
     * @todo Make $out required for 2.0.0.
     * @todo Remove trigger of deprecation notice when preparing for 2.0.0.
     * @todo Remove raiseThrowables logic for 2.0.0.
     * @param Request $request
     * @param Response $response
     * @param callable $out
     * @return Response
     */
    public function __invoke(Request $request, Response $response, callable $out = null)
    {
        $request  = $this->decorateRequest($request);
        $response = $this->decorateResponse($response);

        if (null === $out) {
            trigger_error(sprintf(
                'The third argument to %s() ($out) will be required starting with '
                . 'Stratigility version 2; please see '
                . 'https://docs.zendframework.com/zend-stratigility/migration/to-v2/ for '
                . 'more details on how to update your application to remove this message.',
                __CLASS__
            ), E_USER_DEPRECATED);
        }

        $done   = $out ?: new FinalHandler([], $response);
        $next   = new Next($this->pipeline, $done);
        $next->setResponsePrototype($response);
        if ($this->raiseThrowables) {
            $next->raiseThrowables();
        }

        $result = $next($request, $response);

        return ($result instanceof Response ? $result : $response);
    }

    /**
     * http-interop invocation: single-pass with delegate.
     *
     * Executes the internal pipeline, passing $delegate as the "final
     * handler" in cases when the pipeline exhausts itself.
     *
     * @param Request $request
     * @param DelegateInterface $delegate
     * @return Response
     */
    public function process(Request $request, DelegateInterface $delegate)
    {
        $response = $this->responsePrototype;

        $next = new Next($this->pipeline, $delegate);
        if ($response) {
            $next->setResponsePrototype($response);
        }
        if ($this->raiseThrowables) {
            $next->raiseThrowables();
        }

        $result = $next->process($request);

        return ($result instanceof Response ? $result : $response);
    }

    /**
     * Attach middleware to the pipeline.
     *
     * Each middleware can be associated with a particular path; if that
     * path is matched when that middleware is invoked, it will be processed;
     * otherwise it is skipped.
     *
     * No path means it should be executed every request cycle.
     *
     * A handler CAN implement MiddlewareInterface, but MUST be callable.
     *
     * Handlers with arity >= 4 or those implementing ErrorMiddlewareInterface
     * are considered error handlers, and will be executed when a handler calls
     * $next with an error or raises an exception.
     *
     * @see MiddlewareInterface
     * @see ErrorMiddlewareInterface
     * @see Next
     * @param string|callable|object $path Either a URI path prefix, or middleware.
     * @param null|callable|object $middleware Middleware
     * @return self
     */
    public function pipe($path, $middleware = null)
    {
        if (null === $middleware
            && $this->isValidMiddleware($path)
        ) {
            $middleware = $path;
            $path       = '/';
        }

        // Decorate callable middleware as http-interop middleware if we have
        // a response prototype present.
        if (is_callable($middleware)
            && ! $this->isInteropMiddleware($middleware)
            && ! $this->isErrorMiddleware($middleware)
        ) {
            $middleware = $this->decorateCallableMiddleware($middleware);
        }

        // Ensure we have a valid handler
        if (! $this->isValidMiddleware($middleware)) {
            throw InvalidMiddlewareException::fromValue($middleware);
        }

        $this->pipeline->enqueue(new Route(
            $this->normalizePipePath($path),
            $middleware
        ));

        // @todo Trigger event here with route details?
        return $this;
    }

    /**
     * Enable the "raise throwables" flag.
     *
     * @todo Deprecate this starting in 2.0.0
     * @return void
     */
    public function raiseThrowables()
    {
        $this->raiseThrowables = true;
    }

    /**
     * @param Response $prototype
     * @return void
     */
    public function setResponsePrototype(Response $prototype)
    {
        $this->responsePrototype = $prototype;
    }

    /**
     * @return bool
     */
    public function hasResponsePrototype()
    {
        return $this->responsePrototype instanceof Response;
    }

    /**
     * Normalize a path used when defining a pipe
     *
     * Strips trailing slashes, and prepends a slash.
     *
     * @param string $path
     * @return string
     */
    private function normalizePipePath($path)
    {
        // Prepend slash if missing
        if (empty($path) || $path[0] !== '/') {
            $path = '/' . $path;
        }

        // Trim trailing slash if present
        if (strlen($path) > 1 && '/' === substr($path, -1)) {
            $path = rtrim($path, '/');
        }

        return $path;
    }

    /**
     * Decorate the Request instance
     *
     * @param Request $request
     * @return Http\Request
     */
    private function decorateRequest(Request $request)
    {
        if ($request instanceof Http\Request) {
            return $request;
        }

        return new Http\Request($request);
    }

    /**
     * Decorate the Response instance
     *
     * @param Response $response
     * @return Http\Response
     */
    private function decorateResponse(Response $response)
    {
        if ($response instanceof Http\Response) {
            return $response;
        }

        return new Http\Response($response);
    }

    /**
     * Is the provided middleware argument valid middleware?
     *
     * @param mixed $middleware
     * @return bool
     */
    private function isValidMiddleware($middleware)
    {
        return is_callable($middleware)
            || $middleware instanceof ServerMiddlewareInterface;
    }

    /**
     * Is the provided middleware argument http-interop middleware?
     *
     * @param mixed $middleware
     * @return bool
     */
    private function isInteropMiddleware($middleware)
    {
        return ! is_callable($middleware)
            && $middleware instanceof ServerMiddlewareInterface;
    }

    /**
     * Is the middleware error middleware?
     *
     * @todo Remove for 2.0.0
     * @param mixed $middleware
     * @return bool
     */
    private function isErrorMiddleware($middleware)
    {
        return $middleware instanceof ErrorMiddlewareInterface
            || Utils::getArity($middleware) >= 4;
    }

    /**
     * @param callable $middleware
     * @return ServerMiddlewareInterface|callable Callable, if unable to
     *     decorate the middleware; ServerMiddlewareInterface if it can.
     */
    private function decorateCallableMiddleware(callable $middleware)
    {
        $r = $this->getReflectionFunction($middleware);
        $paramsCount = $r->getNumberOfParameters();

        if ($paramsCount !== 2) {
            return $this->responsePrototype
                ? new Middleware\CallableMiddlewareWrapper($middleware, $this->responsePrototype)
                : $middleware;
        }

        $params = $r->getParameters();
        $type = $params[1]->getClass();
        if (! $type || $type->getName() !== DelegateInterface::class) {
            return $this->responsePrototype
                ? new Middleware\CallableMiddlewareWrapper($middleware, $this->responsePrototype)
                : $middleware;
        }

        return new Middleware\CallableInteropMiddlewareWrapper($middleware);
    }

    /**
     * @param callable $middleware
     * @return \ReflectionFunctionAbstract
     */
    private function getReflectionFunction(callable $middleware)
    {
        if (is_array($middleware)) {
            $class = array_shift($middleware);
            $method = array_shift($middleware);
            return new ReflectionMethod($class, $method);
        }

        if ($middleware instanceof Closure || ! is_object($middleware)) {
            return new ReflectionFunction($middleware);
        }

        return new ReflectionMethod($middleware, '__invoke');
    }
}
