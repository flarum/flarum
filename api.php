<?php

use Flarum\Core;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Zend\Diactoros\Server;
use Zend\Stratigility\MiddlewarePipe;

// Instantiate the application, register providers etc.
$app = require __DIR__.'/system/bootstrap.php';

// Set up everything we need for the API
$app->instance('type', 'api');
$app->register('Flarum\Api\ApiServiceProvider');
$app->register('Flarum\Support\Extensions\ExtensionsServiceProvider');

// Build a middleware pipeline for the API
$api = new MiddlewarePipe();
$api->pipe($app->make('Flarum\Api\Middleware\ReadJsonParameters'));
$api->pipe($app->make('Flarum\Api\Middleware\LoginWithHeader'));

$api->pipe('/api', $app->make('Flarum\Http\RouterMiddleware', ['routes' => $app->make('flarum.api.routes')]));

// Handle errors
if (Core::inDebugMode()) {
	$api->pipe(new \Franzl\Middleware\Whoops\Middleware());
} else {
	$api->pipe(new \Flarum\Api\Middleware\JsonApiErrors());
}

$server = Server::createServer(
	$api,
	$_SERVER,
	$_GET,
	$_POST,
	$_COOKIE,
	$_FILES
);

$server->listen();
