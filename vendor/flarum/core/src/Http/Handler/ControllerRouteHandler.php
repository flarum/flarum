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

use Flarum\Http\Controller\ControllerInterface;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ControllerRouteHandler
{
    /**
     * @var Container
     */
    protected $container;

    /**
     * @var string
     */
    protected $controller;

    /**
     * @param Container $container
     * @param string $controller
     */
    public function __construct(Container $container, $controller)
    {
        $this->container = $container;
        $this->controller = $controller;
    }

    /**
     * @param ServerRequestInterface $request
     * @param array $routeParams
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, array $routeParams)
    {
        $controller = $this->resolveController($this->controller);

        $request = $request->withQueryParams(array_merge($request->getQueryParams(), $routeParams));

        return $controller->handle($request);
    }

    /**
     * @param string $class
     * @return ControllerInterface
     */
    protected function resolveController($class)
    {
        $controller = $this->container->make($class);

        if (! ($controller instanceof ControllerInterface)) {
            throw new InvalidArgumentException(
                'Controller must be an instance of '.ControllerInterface::class
            );
        }

        return $controller;
    }
}
