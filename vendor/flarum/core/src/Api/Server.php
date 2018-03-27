<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Api;

use Flarum\Event\ConfigureMiddleware;
use Flarum\Foundation\Application;
use Flarum\Http\AbstractServer;
use Tobscure\JsonApi\Document;
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

        $path = parse_url($app->url('api'), PHP_URL_PATH);

        if ($app->isInstalled() && $app->isUpToDate()) {
            $pipe->pipe($path, $app->make('Flarum\Api\Middleware\HandleErrors'));

            $pipe->pipe($path, $app->make('Flarum\Http\Middleware\ParseJsonBody'));
            $pipe->pipe($path, $app->make('Flarum\Api\Middleware\FakeHttpMethods'));
            $pipe->pipe($path, $app->make('Flarum\Http\Middleware\StartSession'));
            $pipe->pipe($path, $app->make('Flarum\Http\Middleware\RememberFromCookie'));
            $pipe->pipe($path, $app->make('Flarum\Http\Middleware\AuthenticateWithSession'));
            $pipe->pipe($path, $app->make('Flarum\Http\Middleware\AuthenticateWithHeader'));
            $pipe->pipe($path, $app->make('Flarum\Http\Middleware\SetLocale'));

            event(new ConfigureMiddleware($pipe, $path, $this));

            $pipe->pipe($path, $app->make('Flarum\Http\Middleware\DispatchRoute', ['routes' => $app->make('flarum.api.routes')]));
        } else {
            $pipe->pipe($path, function () {
                $document = new Document;
                $document->setErrors([
                    [
                        'code' => 503,
                        'title' => 'Service Unavailable'
                    ]
                ]);

                return new JsonApiResponse($document, 503);
            });
        }

        return $pipe;
    }
}
