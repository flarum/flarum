<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Zend\Diactoros\Server;
use Zend\Stratigility\MiddlewarePipe;

// Instantiate the application, register providers etc.
$app = require __DIR__.'/system/bootstrap.php';

// Set up everything we need for the API
$app->register('Flarum\Api\ApiServiceProvider');

// Build a middleware pipeline for the API
$api = new MiddlewarePipe();
$api->pipe($app->make('Flarum\Api\Middleware\ReadJsonParameters'));
$api->pipe($app->make('Flarum\Api\Middleware\LoginWithHeader'));

$api->pipe('/api', $app->make('Flarum\Http\RouterMiddleware', ['routes' => $app->make('flarum.api.routes')]));
$api->pipe(new \Franzl\Middleware\Whoops\Middleware());

$server = Server::createServer(
	$api,
	$_SERVER,
	$_GET,
	$_POST,
	$_COOKIE,
	$_FILES
);

$server->listen();
