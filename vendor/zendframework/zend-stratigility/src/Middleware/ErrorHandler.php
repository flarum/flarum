<?php
/**
 * @link      http://github.com/zendframework/zend-stratigility for the canonical source repository
 * @copyright Copyright (c) 2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Stratigility\Middleware;

use ErrorException;
use Exception;
use Interop\Http\Middleware\DelegateInterface;
use Interop\Http\Middleware\ServerMiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;
use Zend\Stratigility\Delegate\CallableDelegateDecorator;
use Zend\Stratigility\Exception\MissingResponseException;

/**
 * Error handler middleware.
 *
 * Use this middleware as the outermost (or close to outermost) middleware
 * layer, and use it to intercept PHP errors and exceptions.
 *
 * The class offers two extension points:
 *
 * - Error response generators.
 * - Listeners.
 *
 * Error response generators are callables with the following signature:
 *
 * <code>
 * function (
 *     Throwable|Exception $e,
 *     ServerRequestInterface $request,
 *     ResponseInterface $response
 * ) : ResponseInterface
 * </code>
 *
 * These are provided the error, and the request responsible; the response
 * provided is the response prototype provided to the ErrorHandler instance
 * itself, and can be used as the basis for returning an error response.
 *
 * An error response generator must be provided as a constructor argument;
 * if not provided, an instance of Zend\Stratigility\Middleware\ErrorResponseGenerator
 * will be used.
 *
 * Listeners use the following signature:
 *
 * <code>
 * function (
 *     Throwable|Exception $e,
 *     ServerRequestInterface $request,
 *     ResponseInterface $response
 * ) : void
 * </code>
 *
 * Listeners are given the error, the request responsible, and the generated
 * error response, and can then react to them. They are best suited for
 * logging and monitoring purposes.
 *
 * Listeners are attached using the attachListener() method, and triggered
 * in the order attached.
 */
final class ErrorHandler implements ServerMiddlewareInterface
{
    /**
     * @var callable[]
     */
    private $listeners = [];

    /**
     * @var callable Routine that will generate the error response.
     */
    private $responseGenerator;

    /**
     * @var ResponseInterface
     */
    private $responsePrototype;

    /**
     * @param ResponseInterface $responsePrototype Empty/prototype response to
     *     update and return when returning an error response.
     * @param callable $responseGenerator Callback that will generate the final
     *     error response; if none is provided, ErrorResponseGenerator is used.
     */
    public function __construct(ResponseInterface $responsePrototype, callable $responseGenerator = null)
    {
        $this->responsePrototype = $responsePrototype;
        $this->responseGenerator = $responseGenerator ?: new ErrorResponseGenerator();
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
     * Attach an error listener.
     *
     * Each listener receives the following three arguments:
     *
     * - Throwable|Exception $error
     * - ServerRequestInterface $request
     * - ResponseInterface $response
     *
     * These instances are all immutable, and the return values of
     * listeners are ignored; use listeners for reporting purposes
     * only.
     *
     * @param callable $listener
     */
    public function attachListener(callable $listener)
    {
        if (in_array($listener, $this->listeners, true)) {
            return;
        }

        $this->listeners[] = $listener;
    }

    /**
     * Middleware to handle errors and exceptions in layers it wraps.
     *
     * Adds an error handler that will convert PHP errors to ErrorException
     * instances.
     *
     * Internally, wraps the call to $next() in a try/catch block, catching
     * all PHP Throwables (PHP 7) and Exceptions (PHP 5.6 and earlier).
     *
     * When an exception is caught, an appropriate error response is created
     * and returned instead; otherwise, the response returned by $next is
     * used.
     *
     * @param ServerRequestInterface $request
     * @param DelegateInterface $delegate
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        set_error_handler($this->createErrorHandler());

        try {
            $response = $delegate->process($request);

            if (! $response instanceof ResponseInterface) {
                throw new MissingResponseException('Application did not return a response');
            }
        } catch (Throwable $e) {
            $response = $this->handleThrowable($e, $request);
        } catch (Exception $e) {
            $response = $this->handleThrowable($e, $request);
        }

        restore_error_handler();

        return $response;
    }

    /**
     * Handles all throwables/exceptions, generating and returning a response.
     *
     * Passes the error, request, and response prototype to createErrorResponse(),
     * triggers all listeners with the same arguments (but using the response
     * returned from createErrorResponse()), and then returns the response.
     *
     * @param Throwable|Exception $e
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    private function handleThrowable($e, ServerRequestInterface $request)
    {
        $generator = $this->responseGenerator;
        $response = $generator($e, $request, $this->responsePrototype);
        $this->triggerListeners($e, $request, $response);
        return $response;
    }

    /**
     * Creates and returns a callable error handler that raises exceptions.
     *
     * Only raises exceptions for errors that are within the error_reporting mask.
     *
     * @return callable
     */
    private function createErrorHandler()
    {
        /**
         * @param int $errno
         * @param string $errstr
         * @param string $errfile
         * @param int $errline
         * @return void
         * @throws ErrorException if error is not within the error_reporting mask.
         */
        return function ($errno, $errstr, $errfile, $errline) {
            if (! (error_reporting() & $errno)) {
                // error_reporting does not include this error
                return;
            }

            throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
        };
    }

    /**
     * Trigger all error listeners.
     *
     * @param Throwable|Exception $error
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return void
     */
    private function triggerListeners($error, ServerRequestInterface $request, ResponseInterface $response)
    {
        array_walk($this->listeners, function ($listener) use ($error, $request, $response) {
            $listener($error, $request, $response);
        });
    }
}
