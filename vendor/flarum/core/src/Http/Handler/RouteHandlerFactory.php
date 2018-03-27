<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Http\Handler;

use Illuminate\Contracts\Container\Container;

class RouteHandlerFactory
{
    /**
     * @var Container
     */
    protected $container;

    /**
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @param string $controller
     * @return ControllerRouteHandler
     */
    public function toController($controller)
    {
        return new ControllerRouteHandler($this->container, $controller);
    }
}
