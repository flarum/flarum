<?php

use Flarum\Core;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Zend\Diactoros\Server;
use Zend\Stratigility\MiddlewarePipe;

// Instantiate the application, register providers etc.
$app = require __DIR__.'/flarum/bootstrap.php';

// Set up everything we need for the frontend
$app->register('Flarum\Admin\AdminServiceProvider');

// Build a middleware pipeline for Flarum
$admin = new MiddlewarePipe();
$admin->pipe($app->make('Flarum\Api\Middleware\ReadJsonParameters'));
$admin->pipe($app->make('Flarum\Admin\Middleware\LoginWithCookieAndCheckAdmin'));
$admin->pipe('/admin', $app->make('Flarum\Http\RouterMiddleware', ['routes' => $app->make('flarum.admin.routes')]));

// Handle errors
if (Core::inDebugMode()) {
    $admin->pipe(new \Franzl\Middleware\Whoops\Middleware());
} else {
    $admin->pipe(new \Flarum\Forum\Middleware\HandleErrors(base_path('error')));
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
