<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Event;

use Flarum\Http\Handler\RouteHandlerFactory;
use Flarum\Http\RouteCollection;

abstract class AbstractConfigureRoutes
{
    /**
     * @var RouteCollection
     */
    public $routes;

    /**
     * @var RouteHandlerFactory
     */
    protected $route;

    /**
     * @param RouteCollection $routes
     * @param RouteHandlerFactory $route
     */
    public function __construct(RouteCollection $routes, RouteHandlerFactory $route)
    {
        $this->routes = $routes;
        $this->route = $route;
    }

    /**
     * @param string $url
     * @param string $name
     * @param string $controller
     */
    public function get($url, $name, $controller)
    {
        $this->route('get', $url, $name, $controller);
    }

    /**
     * @param string $url
     * @param string $name
     * @param string $controller
     */
    public function post($url, $name, $controller)
    {
        $this->route('post', $url, $name, $controller);
    }

    /**
     * @param string $url
     * @param string $name
     * @param string $controller
     */
    public function patch($url, $name, $controller)
    {
        $this->route('patch', $url, $name, $controller);
    }

    /**
     * @param string $url
     * @param string $name
     * @param string $controller
     */
    public function delete($url, $name, $controller)
    {
        $this->route('delete', $url, $name, $controller);
    }

    /**
     * @param string $method
     * @param string $url
     * @param string $name
     * @param string $controller
     */
    protected function route($method, $url, $name, $controller)
    {
        $this->routes->$method($url, $name, $this->toController($controller));
    }

    /**
     * @param string $controller
     * @return callable
     */
    protected function toController($controller)
    {
        return $this->route->toController($controller);
    }
}
