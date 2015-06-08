<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Zend\Diactoros\Server;
use Zend\Stratigility\Http\Response as ZendResponse;
use Zend\Stratigility\MiddlewarePipe;

// Instantiate the application, register providers etc.
$app = require __DIR__.'/system/bootstrap.php';

// Build a middleware pipeline for Flarum
$flarum = new MiddlewarePipe();
$flarum->pipe($app->make('Flarum\Forum\Middleware\LoginWithCookie'));

$api = new MiddlewarePipe();
$flarum->pipe($app->make('Flarum\Api\Middleware\ReadJsonParameters'));
$api->pipe($app->make('Flarum\Api\Middleware\LoginWithHeader'));

$admin = new MiddlewarePipe();
$admin->pipe($app->make('Flarum\Admin\Middleware\LoginWithCookieAndCheckAdmin'));

$flarum->pipe('/api', $api);
$flarum->pipe('/admin', $admin);
$flarum->pipe(function(Request $request, Response $response, $next) use ($app) {
	/** @var Flarum\Http\Router $router */
	$router = $app->make('Flarum\Http\Router');

	return new ZendResponse($router->dispatch($request));
});

$server = Server::createServer(
	$flarum,
	$_SERVER,
	$_GET,
	$_POST,
	$_COOKIE,
	$_FILES
);

$server->listen();
