<?php
define('LARAVEL_START', microtime(true));

require __DIR__.'/vendor/autoload.php';

// Temp while studio doesn't autoload files
require __DIR__.'/core/src/helpers.php';
require __DIR__.'/core/vendor/swiftmailer/swiftmailer/lib/swift_required.php';

$app = new Flarum\Core\Application(
	realpath(__DIR__)
);
$app->instance('path.public', __DIR__.'/..');

Illuminate\Container\Container::setInstance($app);

// LoadConfiguration
if (file_exists($configFile = __DIR__.'/../config.php')) {
	$app->instance('flarum.config', include $configFile);
}

$app->instance('config', $config = new \Illuminate\Config\Repository([
	'view' => [
		'paths' => [
			realpath(base_path('resources/views'))
		],
		'compiled' => realpath(storage_path().'/framework/views'),
	],
	'mail' => [
		'driver' => 'smtp',
		'host' => 'mailtrap.io',
		'port' => 25,
		'from' => ['address' => 'noreply@flarum.org', 'name' => 'Flarum Demo Forum'],
		'encryption' => 'tls',
		'username' => '3041435124c4c166c',
		'password' => 'a6949720835285',
		'sendmail' => '/usr/sbin/sendmail -bs',
		'pretend' => false,
	],
	'cache' => [
		'default' => 'file',
		'stores' => [
			'file' => [
				'driver' => 'file',
				'path'   => storage_path().'/framework/cache',
			],
		],
		'prefix' => 'flarum',
	],
	'filesystems' => [
		'default' => 'local',
		'cloud' => 's3',
		'disks' => [
			'flarum-avatars' => [
				'driver' => 'local',
				'root'   => public_path('assets/avatars')
			],
		],
	],
]));

// ConfigureLogging
$logger = new Monolog\Logger($app->environment());
$logPath = $app->storagePath().'/logs/flarum.log';
$handler = new \Monolog\Handler\StreamHandler($logPath, Monolog\Logger::DEBUG);
$handler->setFormatter(new \Monolog\Formatter\LineFormatter(null, null, true, true));
$logger->pushHandler($handler);

$app->instance('log', $logger);
$app->alias('log', 'Psr\Log\LoggerInterface');

// Register some services
use Flarum\Core;
use Illuminate\Cache\FileStore;
use Illuminate\Cache\Repository;
use Illuminate\Filesystem\Filesystem;

$app->singleton('cache', function($app) {
	$store = new FileStore(new Filesystem(), storage_path('framework/cache'));
	$repository = new Repository($store);
	$repository->setEventDispatcher($app->make('events'));
	return $repository;
});
$app->alias('cache', 'Illuminate\Contracts\Cache\Repository');

// RegisterProviders
$serviceProviders = [

    'Illuminate\Bus\BusServiceProvider',
    'Illuminate\Filesystem\FilesystemServiceProvider',
    'Illuminate\Hashing\HashServiceProvider',
	'Illuminate\Events\EventServiceProvider',
    'Illuminate\Mail\MailServiceProvider',
	'Illuminate\Validation\ValidationServiceProvider',
	'Illuminate\View\ViewServiceProvider',

	'Flarum\Core\DatabaseServiceProvider',

];

if (Core::isInstalled()) {
	$serviceProviders[] = 'Flarum\Core\Settings\SettingsServiceProvider';
	$serviceProviders[] = 'Flarum\Locale\LocaleServiceProvider';
    $serviceProviders[] = 'Flarum\Support\ExtensionsServiceProvider';
    $serviceProviders[] = 'Flarum\Core\CoreServiceProvider';
    $serviceProviders[] = 'Flarum\Console\ConsoleServiceProvider';
}

foreach ($serviceProviders as $provider) {
	$app->register(new $provider($app));
}

// BootProviders
$app->boot();

return $app;
