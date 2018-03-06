<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Update;

use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Http\Handler\RouteHandlerFactory;
use Flarum\Http\RouteCollection;

class UpdateServiceProvider extends AbstractServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function register()
    {
        $this->app->singleton('flarum.update.routes', function () {
            return new RouteCollection;
        });

        $this->loadViewsFrom(__DIR__.'/../../views/install', 'flarum.update');
    }

    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        $this->populateRoutes($this->app->make('flarum.update.routes'));
    }

    /**
     * @param RouteCollection $routes
     */
    protected function populateRoutes(RouteCollection $routes)
    {
        $route = $this->app->make(RouteHandlerFactory::class);

        $routes->get(
            '/',
            'index',
            $route->toController(Controller\IndexController::class)
        );

        $routes->post(
            '/',
            'update',
            $route->toController(Controller\UpdateController::class)
        );
    }
}
