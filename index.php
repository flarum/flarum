<?php

use Flarum\Core;
use Zend\Diactoros\Server;
use Zend\Stratigility\MiddlewarePipe;

// Instantiate the application, register providers etc.
$app = require __DIR__.'/flarum/bootstrap.php';

if ($app->bound('flarum.config')) {
    $app->register('Flarum\Forum\ForumServiceProvider');

    // Build a middleware pipeline for Flarum
    $flarum = new MiddlewarePipe();
    $flarum->pipe($app->make('Flarum\Forum\Middleware\LoginWithCookie'));
    $flarum->pipe($app->make('Flarum\Api\Middleware\ReadJsonParameters'));

    $basePath = parse_url(Core::config('base_url'), PHP_URL_PATH);
    $flarum->pipe($basePath, $app->make('Flarum\Http\RouterMiddleware', ['routes' => $app->make('flarum.forum.routes')]));

    // Handle errors
    if (Core::inDebugMode()) {
    	$flarum->pipe(new \Franzl\Middleware\Whoops\Middleware());
    } else {
    	$flarum->pipe(new \Flarum\Forum\Middleware\HandleErrors(base_path('error')));
    }
} else {
    $app->register('Flarum\Install\InstallServiceProvider');

    $flarum = new MiddlewarePipe();

    $basePath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $flarum->pipe($basePath, $app->make('Flarum\Http\RouterMiddleware', ['routes' => $app->make('flarum.install.routes')]));
    $flarum->pipe(new \Franzl\Middleware\Whoops\Middleware());
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
