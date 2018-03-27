<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @see       http://github.com/zendframework/zend-stratigility for the canonical source repository
 * @copyright Copyright (c) 2015-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-stratigility/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Stratigility;

use Interop\Http\Middleware\DelegateInterface;
use InvalidArgumentException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;
use SplQueue;
use Throwable;

/**
 * Iterate a queue of middlewares and execute them.
 */
class Next implements DelegateInterface
{
    /**
     * @var Dispatch
     */
    private $dispatch;

    /**
     * @var callable|DelegateInterface
     */
    private $nextDelegate;

    /**
     * @var SplQueue
     */
    private $queue;

    /**
     * Flag indicating whether or not the dispatcher should raise throwables
     * when encountered, and whether or not $err arguments should raise them;
     * defaults false.
     *
     * @var bool
     */
    private $raiseThrowables = false;

    /**
     * @var string
     */
    private $removed = '';

    /**
     * Response prototype to use with the $done handler and/or callable
     * middleware when the instance is invoked by http-interop middleware.
     *
     * @var ResponseInterface
     */
    private $responsePrototype;

    /**
     * Constructor.
     *
     * Clones the queue provided to allow re-use.
     *
     * @param SplQueue $queue
     * @param callable|DelegateInterface $done Next delegate to invoke when the
     *     queue is exhausted. Note: this argument becomes optional starting in
     *     2.0.0.
     * @throws InvalidArgumentException for a non-callable, non-delegate $done
     *     argument.
     */
    public function __construct(SplQueue $queue, $done)
    {
        if (! (is_callable($done) || $done instanceof DelegateInterface)) {
            throw new InvalidArgumentException(sprintf(
                'Invalid "$done" argument provided to %s; must be callable '
                . 'or a %s instance; received %s',
                get_class($this),
                DelegateInterface::class,
                is_object($done) ? get_class($done) : gettype($done)
            ));
        }

        $this->queue        = clone $queue;
        $this->nextDelegate = $done;
        $this->dispatch     = new Dispatch();
    }

    /**
     * Call the next Route in the queue.
     *
     * Next requires that a request and response are provided; these will be
     * passed to any middleware invoked, including the $done callable, if
     * invoked.
     *
     * If the $err value is not null, the invocation is considered to be an
     * error invocation, and Next will search for the next error middleware
     * to dispatch, passing it $err along with the request and response.
     *
     * Once dispatch is complete, if the result is a response instance, that
     * value will be returned; otherwise, the currently registered response
     * instance will be returned.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param null|mixed $err This argument is deprecated as of 1.3.0, and will
     *     be removed in 2.0.0.
     * @return ResponseInterface
     */
    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        $err = null
    ) {
        if ($err !== null && $this->raiseThrowables) {
            $this->raiseThrowableFromError($err);
        }

        if (null !== $err) {
            $this->triggerErrorDeprecation();
        }

        if (! $this->responsePrototype) {
            $this->setResponsePrototype($response);
        }

        $dispatch = $this->dispatch;
        $done     = $this->nextDelegate;
        $request  = $this->resetPath($request);

        // No middleware remains; done
        if ($this->queue->isEmpty()) {
            return $this->dispatchNextDelegate($done, $request, $response, $err);
        }

        $layer           = $this->queue->dequeue();
        $path            = $request->getUri()->getPath() ?: '/';
        $route           = $layer->path;
        $normalizedRoute = (strlen($route) > 1) ? rtrim($route, '/') : $route;

        // Skip if layer path does not match current url
        if (substr(strtolower($path), 0, strlen($normalizedRoute)) !== strtolower($normalizedRoute)) {
            return $this($request, $response, $err);
        }

        // Skip if match is not at a border ('/', '.', or end)
        $border = $this->getBorder($path, $normalizedRoute);
        if ($border && '/' !== $border && '.' !== $border) {
            return $this($request, $response, $err);
        }

        // Trim off the part of the url that matches the layer route
        if (! empty($route) && $route !== '/') {
            $request = $this->stripRouteFromPath($request, $route);
        }

        $result = $dispatch($layer, $err, $request, $response, $this);

        return ($result instanceof ResponseInterface ? $result : $response);
    }

    /**
     * @param RequestInterface $request
     * @return ResponseInterface
     * @throws Exception\MissingResponsePrototypeException
     * @throws Exception\InvalidRequestTypeException
     */
    public function process(RequestInterface $request)
    {
        $dispatch = $this->dispatch;
        $done     = $this->nextDelegate;
        $request  = $this->resetPath($request);

        // No middleware remains; done
        if ($this->queue->isEmpty()) {
            return $this->dispatchNextDelegate($done, $request);
        }

        $layer           = $this->queue->dequeue();
        $path            = $request->getUri()->getPath() ?: '/';
        $route           = $layer->path;
        $normalizedRoute = (strlen($route) > 1) ? rtrim($route, '/') : $route;

        // Skip if layer path does not match current url
        if (substr(strtolower($path), 0, strlen($normalizedRoute)) !== strtolower($normalizedRoute)) {
            return $this->process($request);
        }

        // Skip if match is not at a border ('/', '.', or end)
        $border = $this->getBorder($path, $normalizedRoute);
        if ($border && '/' !== $border && '.' !== $border) {
            return $this->process($request);
        }

        // Trim off the part of the url that matches the layer route
        if (! empty($route) && $route !== '/') {
            $request = $this->stripRouteFromPath($request, $route);
        }

        $result = $dispatch->process($layer, $request, $this);

        if (! $result instanceof ResponseInterface) {
            return $this->getResponsePrototype();
        }

        return $result;
    }

    /**
     * Toggle the "raise throwables" flag on.
     *
     * @return void
     */
    public function raiseThrowables()
    {
        $this->raiseThrowables = true;
        $this->dispatch->raiseThrowables();
    }

    /**
     * @param ResponseInterface $prototype
     * @return void
     */
    public function setResponsePrototype(ResponseInterface $prototype)
    {
        $this->responsePrototype = $prototype;
        $this->dispatch->setResponsePrototype($prototype);
    }

    /**
     * Reset the path, if a segment was previously stripped
     *
     * @param RequestInterface $request
     * @return RequestInterface
     */
    private function resetPath(RequestInterface $request)
    {
        if (! $this->removed) {
            return $request;
        }

        $uri  = $request->getUri();
        $path = $uri->getPath();

        if (strlen($path) >= strlen($this->removed)
            && 0 === strpos($path, $this->removed)
        ) {
            $path = str_replace($this->removed, '', $path);
        }

        $resetPath = $this->removed . $path;

        // Strip trailing slash if current path does not contain it and
        // original path did not have it
        if ('/' === $path && '/' !== substr($this->removed, -1)) {
            $resetPath = rtrim($resetPath, '/');
        }

        // Normalize to remove double-slashes
        $resetPath = str_replace('//', '/', $resetPath);

        $new  = $uri->withPath($resetPath);
        $this->removed = '';
        return $request->withUri($new);
    }

    /**
     * Determine the border between the request path and current route
     *
     * @param string $path
     * @param string $route
     * @return string
     */
    private function getBorder($path, $route)
    {
        if ($route === '/') {
            return '/';
        }
        $routeLength = strlen($route);
        return (strlen($path) > $routeLength) ? $path[$routeLength] : '';
    }

    /**
     * Strip the route from the request path
     *
     * @param RequestInterface $request
     * @param string $route
     * @return RequestInterface
     */
    private function stripRouteFromPath(RequestInterface $request, $route)
    {
        $this->removed = $route;

        $uri  = $request->getUri();
        $path = $this->getTruncatedPath($route, $uri->getPath());
        $new  = $uri->withPath($path);

        // Root path of route is treated differently
        if ($path === '/' && '/' === substr($uri->getPath(), -1)) {
            $this->removed .= '/';
        }

        return $request->withUri($new);
    }

    /**
     * Strip the segment from the start of the given path.
     *
     * @param string $segment
     * @param string $path
     * @return string Truncated path
     * @throws RuntimeException if the segment does not begin the path.
     */
    private function getTruncatedPath($segment, $path)
    {
        if ($path === $segment) {
            // Segment and path are same; return empty string
            return '';
        }

        $segmentLength = strlen($segment);
        if (strlen($path) > $segmentLength) {
            // Strip segment from start of path
            return substr($path, $segmentLength);
        }

        if ('/' === substr($segment, -1)) {
            // Re-try by submitting with / stripped from end of segment
            return $this->getTruncatedPath(rtrim($segment, '/'), $path);
        }

        // Segment is longer than path. There's an issue
        throw new RuntimeException(
            'Layer and request path have gone out of sync'
        );
    }

    /**
     * @return ResponseInterface
     * @throws Exception\MissingResponsePrototypeException
     */
    private function getResponsePrototype()
    {
        if ($this->responsePrototype) {
            return $this->responsePrototype;
        }

        throw new Exception\MissingResponsePrototypeException(
            'Invoking callable middleware or final handler following http-interop '
            . 'middleware, but no response prototype is present; please inject '
            . 'one in your MiddlewarePipe or ensure Stratigility callable '
            . 'middleware exists in the outer layer of your application.'
        );
    }

    /**
     * @param RequestInterface $request
     * @return bool
     * @throws Exception\InvalidRequestTypeException
     */
    private function validateServerRequest(RequestInterface $request)
    {
        if ($request instanceof ServerRequestInterface) {
            return true;
        }

        throw new Exception\InvalidRequestTypeException(sprintf(
            'Invoking callable middleware or final handler following http-interop '
            . 'middleware, but did not receive a %s; please ensure that your '
            . 'middleware always calls %s::process() using one.',
            ServerRequestInterface::class,
            DelegateInterface::class
        ));
    }

    /**
     * Dispatch the next delegate.
     *
     * For DelegateInterface implementations, calls the process method with
     * only the request instance.
     *
     * For callables, calls with request, response, and error.
     *
     * @param callable|DelegateInterface $nextDelegate
     * @param RequestInterface $request
     * @param ResponseInterface|null $response
     * @param mixed $err
     * @return ResponseInterface
     */
    private function dispatchNextDelegate(
        $nextDelegate,
        RequestInterface $request,
        ResponseInterface $response = null,
        $err = null
    ) {
        if ($nextDelegate instanceof DelegateInterface
            && (! $nextDelegate instanceof Next || $err === null)
        ) {
            return $nextDelegate->process($request);
        }

        $response = $response ?: $this->getResponsePrototype();
        $this->validateServerRequest($request);
        return $nextDelegate($request, $response, $err);
    }

    /**
     * @param mixed $err
     * @throws Throwable|\Exception
     */
    private function raiseThrowableFromError($err)
    {
        if ($err instanceof Throwable
            || $err instanceof \Exception
        ) {
            throw $err;
        }

        $this->triggerErrorDeprecation();
        throw Exception\MiddlewareException::fromErrorValue($err);
    }

    /**
     * @todo Remove for 2.0.0
     */
    private function triggerErrorDeprecation()
    {
        trigger_error(
            'Usage of error middleware is deprecated as of 1.3.0, and will be removed in 2.0.0; '
            . 'please see https://docs.zendframework.com/zend-stratigility/migration/to-v2/ '
            . 'for details on how to update your application to remove this message.',
            E_USER_DEPRECATED
        );
    }
}
