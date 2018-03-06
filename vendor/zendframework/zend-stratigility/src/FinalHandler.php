<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @see       http://github.com/zendframework/zend-stratigility for the canonical source repository
 * @copyright Copyright (c) 2015-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-stratigility/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Stratigility;

use Exception;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Zend\Escaper\Escaper;

/**
 * Handle incomplete requests
 *
 * @deprecated since 1.3.0; will be removed with 2.0.0. Please see
 *     https://docs.zendframework.com/zend-stratigility/migration/to-v2/
 *     for more information on how to update your code for forwards
 *     compatibility.
 */
class FinalHandler
{
    /**
     * Original response body size.
     *
     * @var int
     */
    private $bodySize = 0;

    /**
     * @var array
     */
    private $options;

    /**
     * Original response provided to the middleware.
     *
     * @var null|ResponseInterface
     */
    private $response;

    /**
     * @param array $options Options that change default override behavior.
     * @param null|ResponseInterface $response Original response, if any.
     */
    public function __construct(array $options = [], ResponseInterface $response = null)
    {
        $this->options = $options;

        $this->setOriginalResponse($response);
    }

    /**
     * Handle incomplete requests
     *
     * This handler should only ever be invoked if Next exhausts its stack.
     *
     * When that happens, one of three possibilities exists:
     *
     * - If an $err is present, create a 500 status with error details.
     * - If the instance composes a response, and it differs from the response
     *   passed during invocation, return the invocation response; this is
     *   indicative of middleware calling $next to allow post-processing of
     *   a populated response.
     * - Otherwise, a 404 status is created.
     *
     * @param RequestInterface $request Request instance.
     * @param ResponseInterface $response Response instance.
     * @param mixed $err
     * @return ResponseInterface
     */
    public function __invoke(RequestInterface $request, ResponseInterface $response, $err = null)
    {
        if ($err) {
            return $this->handleError($err, $request, $response);
        }

        // Return provided response if it does not match the one provided at
        // instantiation; this is an indication of calling `$next` in the final
        // registered middleware and providing a new response instance.
        if ($this->response && $this->response !== $response) {
            return $response;
        }

        // If the response passed is the same as the one at instantiation,
        // check to see if the body size has changed; if it has, return
        // the response, as the message body has been written to.
        if ($this->response
            && $this->response === $response
            && $this->bodySize !== $response->getBody()->getSize()
        ) {
            return $response;
        }

        return $this->create404($request, $response);
    }

    /**
     * Set the original response and response body size for comparison.
     *
     * @param ResponseInterface $response
     */
    public function setOriginalResponse(ResponseInterface $response = null)
    {
        $this->response = $response;

        if ($response) {
            $this->bodySize = $response->getBody()->getSize();
        }
    }

    /**
     * Handle an error condition
     *
     * Use the $error to create details for the response.
     *
     * @param mixed $error
     * @param RequestInterface $request Request instance.
     * @param ResponseInterface $response Response instance.
     * @return ResponseInterface
     */
    private function handleError($error, RequestInterface $request, ResponseInterface $response)
    {
        $statusCode = Utils::getStatusCode($error, $response);
        $reasonPhrase = $response->getStatusCode() === $statusCode
                      ? $response->getReasonPhrase()
                      : '';
        $response = $response->withStatus($statusCode, $reasonPhrase);
        $message = $response->getReasonPhrase() ?: 'Unknown Error';

        if (isset($this->options['env']) && $this->options['env'] !== 'production') {
            $message = $this->createDevelopmentErrorMessage($error);
        }

        $response = $this->completeResponse($response, $message);

        $this->triggerError($error, $request, $response);

        return $response;
    }

    /**
     * Create a 404 status in the response
     *
     * @param RequestInterface $request Request instance.
     * @param ResponseInterface $response Response instance.
     * @return ResponseInterface
     */
    private function create404(RequestInterface $request, ResponseInterface $response)
    {
        $response        = $response->withStatus(404);
        $uri             = $this->getUriFromRequest($request);
        $escaper         = new Escaper();
        $message         = sprintf(
            "Cannot %s %s\n",
            $escaper->escapeHtml($request->getMethod()),
            $escaper->escapeHtml((string) $uri)
        );

        return $this->completeResponse($response, $message);
    }

    /**
     * Create a complete error message for development purposes.
     *
     * Creates an error message with full error details:
     *
     * - If the error is an exception, creates a message that includes the full
     *   stack trace.
     * - If the error is an object that defines `__toString()`, creates a
     *   message by casting the error to a string.
     * - If the error is not an object, casts the error to a string.
     * - Otherwise, cerates a generic error message indicating the class type.
     *
     * In all cases, the error message is escaped for use in HTML.
     *
     * @param mixed $error
     * @return string
     */
    private function createDevelopmentErrorMessage($error)
    {
        if ($error instanceof Exception) {
            $message  = $error;
        } elseif (is_object($error) && ! method_exists($error, '__toString')) {
            $message = sprintf('Error of type "%s" occurred', get_class($error));
        } else {
            $message = (string) $error;
        }

        $escaper = new Escaper();
        return $escaper->escapeHtml($message);
    }

    /**
     * Trigger the error listener, if present
     *
     * If no `onerror` option is present, or if it is not callable, does
     * nothing.
     *
     * If the request is not an Http\Request, casts it to one prior to invoking
     * the error handler.
     *
     * If the response is not an Http\Response, casts it to one prior to invoking
     * the error handler.
     *
     * @param mixed $error
     * @param RequestInterface $request
     * @param ResponseInterface $response
     */
    private function triggerError($error, RequestInterface $request, ResponseInterface $response)
    {
        if (! isset($this->options['onerror'])
            || ! is_callable($this->options['onerror'])
        ) {
            return;
        }

        $onError = $this->options['onerror'];
        $onError(
            $error,
            ($request instanceof Http\Request) ? $request : new Http\Request($request),
            ($response instanceof Http\Response) ? $response : new Http\Response($response)
        );
    }

    /**
     * Retrieve the URI from the request.
     *
     * If the request instance is a Stratigility decorator, pull the URI from
     * the original request; otherwise, pull it directly.
     *
     * @param RequestInterface $request
     * @return \Psr\Http\Message\UriInterface
     */
    private function getUriFromRequest(RequestInterface $request)
    {
        if (false !== ($original = $request->getAttribute('originalRequest', false))) {
            return $original->getUri();
        }

        return $request->getUri();
    }

    /**
     * Write the given message to the response and mark it complete.
     *
     * @param ResponseInterface $response
     * @param string $message
     * @return ResponseInterface
     */
    private function completeResponse(ResponseInterface $response, $message)
    {
        $response->getBody()->write($message);
        return $response;
    }
}
