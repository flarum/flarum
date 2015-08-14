<?php

use Flarum\Core;
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

$adminPath = parse_url(Core::config('admin_url'), PHP_URL_PATH);
$admin->pipe($adminPath, $app->make('Flarum\Http\RouterMiddleware', ['routes' => $app->make('flarum.admin.routes')]));

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
