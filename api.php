<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Flarum\Api\Middleware\JsonApiErrors;
use Flarum\Core;
use Franzl\Middleware\Whoops\Middleware as WhoopsMiddleware;
use Zend\Diactoros\Server;
use Zend\Stratigility\MiddlewarePipe;

$app = require __DIR__.'/flarum/bootstrap.php';

$app->register('Flarum\Api\ApiServiceProvider');

$api = new MiddlewarePipe();
$api->pipe($app->make('Flarum\Api\Middleware\ReadJsonParameters'));
$api->pipe($app->make('Flarum\Api\Middleware\LoginWithHeader'));

$apiPath = parse_url(Core::url('api'), PHP_URL_PATH);
$router = $app->make('Flarum\Http\RouterMiddleware', ['routes' => $app->make('flarum.api.routes')]);

$api->pipe($apiPath, $router);

$api->pipe(new JsonApiErrors());

$server = Server::createServer(
    $api,
    $_SERVER,
    $_GET,
    $_POST,
    $_COOKIE,
    $_FILES
);

$server->listen();
