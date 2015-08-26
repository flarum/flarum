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

$app->register('Flarum\Admin\AdminServiceProvider');

$admin = new MiddlewarePipe();
$admin->pipe($app->make('Flarum\Api\Middleware\ReadJsonParameters'));
$admin->pipe($app->make('Flarum\Admin\Middleware\LoginWithCookieAndCheckAdmin'));

$adminPath = parse_url(Core::url('admin'), PHP_URL_PATH);
$router = $app->make('Flarum\Http\RouterMiddleware', ['routes' => $app->make('flarum.admin.routes')]);

$admin->pipe($adminPath, $router);

if (Core::inDebugMode()) {
    $admin->pipe(new WhoopsMiddleware());
} else {
    $admin->pipe(new HandleErrors(base_path('error')));
}

$server = Server::createServer(
    $admin,
    $_SERVER,
    $_GET,
    $_POST,
    $_COOKIE,
    $_FILES
);

$server->listen();
