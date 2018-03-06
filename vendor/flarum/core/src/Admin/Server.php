<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Admin;

use Flarum\Event\ConfigureMiddleware;
use Flarum\Foundation\Application;
use Flarum\Http\AbstractServer;
use Flarum\Http\Middleware\HandleErrors;
use Zend\Stratigility\MiddlewarePipe;

class Server extends AbstractServer
{
    /**
     * {@inheritdoc}
     */
    protected function getMiddleware(Application $app)
    {
        $pipe = new MiddlewarePipe;
        $pipe->raiseThrowables();

        if ($app->isInstalled()) {
            $path = parse_url($app->url('admin'), PHP_URL_PATH);
            $errorDir = __DIR__.'/../../error';

            // All requests should first be piped through our global error handler
            $debugMode = ! $app->isUpToDate() || $app->inDebugMode();
            $pipe->pipe($path, new HandleErrors($errorDir, $app->make('log'), $debugMode));

            if ($app->isUpToDate()) {
                $pipe->pipe($path, $app->make('Flarum\Http\Middleware\ParseJsonBody'));
                $pipe->pipe($path, $app->make('Flarum\Http\Middleware\StartSession'));
                $pipe->pipe($path, $app->make('Flarum\Http\Middleware\RememberFromCookie'));
                $pipe->pipe($path, $app->make('Flarum\Http\Middleware\AuthenticateWithSession'));
                $pipe->pipe($path, $app->make('Flarum\Http\Middleware\SetLocale'));
                $pipe->pipe($path, $app->make('Flarum\Admin\Middleware\RequireAdministrateAbility'));

                event(new ConfigureMiddleware($pipe, $path, $this));

                $pipe->pipe($path, $app->make('Flarum\Http\Middleware\DispatchRoute', ['routes' => $app->make('flarum.admin.routes')]));
            } else {
                $app->register('Flarum\Update\UpdateServiceProvider');

                $pipe->pipe($path, $app->make('Flarum\Http\Middleware\DispatchRoute', ['routes' => $app->make('flarum.update.routes')]));
            }
        }

        return $pipe;
    }
}
