<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Forum;

use Flarum\Event\ConfigureMiddleware;
use Flarum\Foundation\Application;
use Flarum\Http\AbstractServer;
use Flarum\Http\Middleware\HandleErrors;
use Zend\Diactoros\Response\HtmlResponse;
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

        $path = parse_url($app->url(), PHP_URL_PATH);
        $errorDir = __DIR__.'/../../error';

        if (! $app->isInstalled()) {
            $app->register('Flarum\Install\InstallServiceProvider');

            $pipe->pipe($path, new HandleErrors($errorDir, $app->make('log'), true));

            $pipe->pipe($path, $app->make('Flarum\Http\Middleware\StartSession'));
            $pipe->pipe($path, $app->make('Flarum\Http\Middleware\DispatchRoute', ['routes' => $app->make('flarum.install.routes')]));
        } elseif ($app->isUpToDate() && ! $app->isDownForMaintenance()) {
            $pipe->pipe($path, new HandleErrors($errorDir, $app->make('log'), $app->inDebugMode()));

            $pipe->pipe($path, $app->make('Flarum\Http\Middleware\ParseJsonBody'));
            $pipe->pipe($path, $app->make('Flarum\Http\Middleware\StartSession'));
            $pipe->pipe($path, $app->make('Flarum\Http\Middleware\RememberFromCookie'));
            $pipe->pipe($path, $app->make('Flarum\Http\Middleware\AuthenticateWithSession'));
            $pipe->pipe($path, $app->make('Flarum\Http\Middleware\SetLocale'));

            event(new ConfigureMiddleware($pipe, $path, $this));

            $pipe->pipe($path, $app->make('Flarum\Http\Middleware\DispatchRoute', ['routes' => $app->make('flarum.forum.routes')]));
        } else {
            $pipe->pipe($path, function () use ($errorDir) {
                return new HtmlResponse(file_get_contents($errorDir.'/503.html', 503));
            });
        }

        return $pipe;
    }
}
