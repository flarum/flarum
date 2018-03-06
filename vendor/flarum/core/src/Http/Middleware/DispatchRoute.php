<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Http\Middleware;

use FastRoute\Dispatcher;
use Flarum\Http\Exception\MethodNotAllowedException;
use Flarum\Http\Exception\RouteNotFoundException;
use Flarum\Http\RouteCollection;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class DispatchRoute
{
    /**
     * @var RouteCollection
     */
    protected $routes;

    /**
     * @var Dispatcher
     */
    protected $dispatcher;

    /**
     * Create the middleware instance.
     *
     * @param RouteCollection $routes
     */
    public function __construct(RouteCollection $routes)
    {
        $this->routes = $routes;
    }

    /**
     * Dispatch the given request to our route collection.
     *
     * @param Request $request
     * @param Response $response
     * @param callable $out
     * @return Response
     * @throws MethodNotAllowedException
     * @throws RouteNotFoundException
     */
    public function __invoke(Request $request, Response $response, callable $out = null)
    {
        $method = $request->getMethod();
        $uri = $request->getUri()->getPath() ?: '/';

        $routeInfo = $this->getDispatcher()->dispatch($method, $uri);

        switch ($routeInfo[0]) {
            case Dispatcher::NOT_FOUND:
                throw new RouteNotFoundException;
            case Dispatcher::METHOD_NOT_ALLOWED:
                throw new MethodNotAllowedException;
            case Dispatcher::FOUND:
                $handler = $routeInfo[1];
                $parameters = $routeInfo[2];

                return $handler($request, $parameters);
        }
    }

    protected function getDispatcher()
    {
        if (! isset($this->dispatcher)) {
            $this->dispatcher = new Dispatcher\GroupCountBased($this->routes->getRouteData());
        }

        return $this->dispatcher;
    }
}
