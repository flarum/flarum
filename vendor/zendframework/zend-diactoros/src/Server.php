<?php
/**
 * @see       https://github.com/zendframework/zend-diactoros for the canonical source repository
 * @copyright Copyright (c) 2015-2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-diactoros/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Diactoros;

use OutOfBoundsException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * "Serve" incoming HTTP requests
 *
 * Given a callback, takes an incoming request, dispatches it to the
 * callback, and then sends a response.
 */
class Server
{
    /**
     * @var callable
     */
    private $callback;

    /**
     * Response emitter to use; by default, uses Response\SapiEmitter.
     *
     * @var Response\EmitterInterface
     */
    private $emitter;

    /**
     * @var ServerRequestInterface
     */
    private $request;

    /**
     * @var ResponseInterface
     */
    private $response;

    /**
     * Constructor
     *
     * Given a callback, a request, and a response, we can create a server.
     *
     * @param callable $callback
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     */
    public function __construct(
        callable $callback,
        ServerRequestInterface $request,
        ResponseInterface $response
    ) {
        $this->callback = $callback;
        $this->request  = $request;
        $this->response = $response;
    }

    /**
     * Allow retrieving the request, response and callback as properties
     *
     * @param string $name
     * @return mixed
     * @throws OutOfBoundsException for invalid properties
     */
    public function __get($name)
    {
        if (! property_exists($this, $name)) {
            throw new OutOfBoundsException('Cannot retrieve arbitrary properties from server');
        }
        return $this->{$name};
    }

    /**
     * Set alternate response emitter to use.
     *
     * @param Response\EmitterInterface $emitter
     */
    public function setEmitter(Response\EmitterInterface $emitter)
    {
        $this->emitter = $emitter;
    }

    /**
     * Create a Server instance
     *
     * Creates a server instance from the callback and the following
     * PHP environmental values:
     *
     * - server; typically this will be the $_SERVER superglobal
     * - query; typically this will be the $_GET superglobal
     * - body; typically this will be the $_POST superglobal
     * - cookies; typically this will be the $_COOKIE superglobal
     * - files; typically this will be the $_FILES superglobal
     *
     * @param callable $callback
     * @param array $server
     * @param array $query
     * @param array $body
     * @param array $cookies
     * @param array $files
     * @return static
     */
    public static function createServer(
        callable $callback,
        array $server,
        array $query,
        array $body,
        array $cookies,
        array $files
    ) {
        $request  = ServerRequestFactory::fromGlobals($server, $query, $body, $cookies, $files);
        $response = new Response();
        return new static($callback, $request, $response);
    }

    /**
     * Create a Server instance from an existing request object
     *
     * Provided a callback, an existing request object, and optionally an
     * existing response object, create and return the Server instance.
     *
     * If no Response object is provided, one will be created.
     *
     * @param callable $callback
     * @param ServerRequestInterface $request
     * @param null|ResponseInterface $response
     * @return static
     */
    public static function createServerFromRequest(
        callable $callback,
        ServerRequestInterface $request,
        ResponseInterface $response = null
    ) {
        if (! $response) {
            $response = new Response();
        }
        return new static($callback, $request, $response);
    }

    /**
     * "Listen" to an incoming request
     *
     * If provided a $finalHandler, that callable will be used for
     * incomplete requests.
     *
     * @param null|callable $finalHandler
     */
    public function listen(callable $finalHandler = null)
    {
        $callback = $this->callback;

        $response = $callback($this->request, $this->response, $finalHandler);
        if (! $response instanceof ResponseInterface) {
            $response = $this->response;
        }

        $this->getEmitter()->emit($response);
    }

    /**
     * Retrieve the current response emitter.
     *
     * If none has been registered, lazy-loads a Response\SapiEmitter.
     *
     * @return Response\EmitterInterface
     */
    private function getEmitter()
    {
        if (! $this->emitter) {
            $this->emitter = new Response\SapiEmitter();
        }

        return $this->emitter;
    }
}
