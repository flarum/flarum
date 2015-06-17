<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Zend\Diactoros\Server;
use Zend\Stratigility\MiddlewarePipe;

// Instantiate the application, register providers etc.
$app = require __DIR__.'/system/bootstrap.php';

// Set up everything we need for the frontend
$app->register('Flarum\Forum\ForumServiceProvider');

// Build a middleware pipeline for Flarum
$flarum = new MiddlewarePipe();
$flarum->pipe($app->make('Flarum\Forum\Middleware\LoginWithCookie'));
$flarum->pipe($app->make('Flarum\Api\Middleware\ReadJsonParameters'));
$flarum->pipe($app->make('Flarum\Http\RouterMiddleware', ['routes' => $app->make('flarum.forum.routes')]));
$flarum->pipe(new \Franzl\Middleware\Whoops\Middleware());

$server = Server::createServer(
	$flarum,
	$_SERVER,
	$_GET,
	$_POST,
	$_COOKIE,
	$_FILES
);

$server->listen();
