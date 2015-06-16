<?php
define('LARAVEL_START', microtime(true));

require __DIR__.'/vendor/autoload.php';

$app = new Illuminate\Foundation\Application(
	realpath(__DIR__)
);
$app->instance('path.public', __DIR__.'/..');

// DetectEnvironment
Dotenv::load($app->basePath(), $app->environmentFile());

$app->detectEnvironment(function()
{
	return env('APP_ENV', 'production');
});

// LoadConfiguration
$bootstrappers = [
	'Illuminate\Foundation\Bootstrap\LoadConfiguration',
	//'Illuminate\Foundation\Bootstrap\HandleExceptions',
];

$app->bootstrapWith($bootstrappers);

// ConfigureLogging
$logger = new Monolog\Logger($app->environment());
$logPath = $app->storagePath().'/logs/flarum.log';
$handler = new \Monolog\Handler\StreamHandler($logPath, Monolog\Logger::DEBUG);
$handler->setFormatter(new \Monolog\Formatter\LineFormatter(null, null, true, true));
$logger->pushHandler($handler);

$app->instance('log', $logger);
$app->alias('log', 'Psr\Log\LoggerInterface');

// RegisterFacades
use Illuminate\Support\Facades\Facade;

Facade::clearResolvedInstances();
Facade::setFacadeApplication($app);

// RegisterProviders
$app->registerConfiguredProviders();

// BootProviders
$app->boot();

use Illuminate\Foundation\Console\Kernel as IlluminateConsoleKernel;

class ConsoleKernel extends IlluminateConsoleKernel {
	protected $commands = [];
}

$app->singleton(
	'Illuminate\Contracts\Http\Kernel',
	'HttpKernel'
);

$app->singleton(
	'Illuminate\Contracts\Console\Kernel',
	'ConsoleKernel'
);

$app->singleton(
	'Illuminate\Contracts\Debug\ExceptionHandler',
	'Illuminate\Foundation\Exceptions\Handler'
);

return $app;
