<?php
define('LARAVEL_START', microtime(true));

require __DIR__.'/vendor/autoload.php';

$app = new Illuminate\Foundation\Application(
	realpath(__DIR__)
);
$app->instance('path.public', __DIR__.'/..');

$middleware = [
	'Illuminate\Foundation\Http\Middleware\CheckForMaintenanceMode',
	'Illuminate\Cookie\Middleware\EncryptCookies',
	'Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse',
	'Illuminate\Session\Middleware\StartSession',
	'Illuminate\View\Middleware\ShareErrorsFromSession',
	// 'App\Http\Middleware\VerifyCsrfToken',
];

$bootstrappers = [
	'Illuminate\Foundation\Bootstrap\DetectEnvironment',
	'Illuminate\Foundation\Bootstrap\LoadConfiguration',
	'Illuminate\Foundation\Bootstrap\ConfigureLogging',
	//'Illuminate\Foundation\Bootstrap\HandleExceptions',
	'Illuminate\Foundation\Bootstrap\RegisterFacades',
	'Illuminate\Foundation\Bootstrap\RegisterProviders',
	'Illuminate\Foundation\Bootstrap\BootProviders',
];

$app->bootstrapWith($bootstrappers);

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
