<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Zend\Diactoros\Server;
use Zend\Stratigility\MiddlewarePipe;

// Instantiate the application, register providers etc.
$app = require __DIR__.'/system/bootstrap.php';

// Set up everything we need for the frontend
$app->register('Flarum\Admin\AdminServiceProvider');

$admin = new MiddlewarePipe();
$admin->pipe($app->make('Flarum\Admin\Middleware\LoginWithCookieAndCheckAdmin'));
$admin->pipe($app->make('Flarum\Http\RouterMiddleware', ['routes' => $app->make('flarum.admin.routes')]));
$admin->pipe(new \Franzl\Middleware\Whoops\Middleware());

$server = Server::createServer(
	$admin,
	$_SERVER,
	$_GET,
	$_POST,
	$_COOKIE,
	$_FILES
);

$server->listen();
