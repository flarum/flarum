<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Flarum\Core;
use Flarum\Forum\Middleware\HandleErrors;
use Franzl\Middleware\Whoops\Middleware as WhoopsMiddleware;
use Zend\Diactoros\Server;
use Zend\Stratigility\MiddlewarePipe;

$app = require __DIR__.'/flarum/bootstrap.php';

// If Flarum's configuration exists, then we can assume that installation has
// been completed. We will set up a middleware pipe to route the request through
// to one of the main forum actions.
if (Core::isInstalled()) {
    $app->register('Flarum\Forum\ForumServiceProvider');

    $flarum = new MiddlewarePipe();
    $flarum->pipe($app->make('Flarum\Forum\Middleware\LoginWithCookie'));
    $flarum->pipe($app->make('Flarum\Api\Middleware\ReadJsonParameters'));

    $basePath = parse_url(Core::url(), PHP_URL_PATH);
    $router = $app->make('Flarum\Http\RouterMiddleware', ['routes' => $app->make('flarum.forum.routes')]);

    $flarum->pipe($basePath, $router);

    if (Core::inDebugMode()) {
        $flarum->pipe(new WhoopsMiddleware());
    } else {
        $flarum->pipe(new HandleErrors(base_path('error')));
    }
} else {
    $app->register('Flarum\Install\InstallServiceProvider');

    $flarum = new MiddlewarePipe();

    $basePath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $router = $app->make('Flarum\Http\RouterMiddleware', ['routes' => $app->make('flarum.install.routes')]);
    $flarum->pipe($basePath, $router);
    $flarum->pipe(new WhoopsMiddleware());
}

$server = Server::createServer(
    $flarum,
    $_SERVER,
    $_GET,
    $_POST,
    $_COOKIE,
    $_FILES
);

$server->listen();
