<?php
define('LARAVEL_START', microtime(true));

require __DIR__.'/vendor/autoload.php';

// Temp while franzliedke/studio doesn't autoload files
if (file_exists(__DIR__.'/core')) {
    require __DIR__.'/core/src/helpers.php';
    require __DIR__.'/core/vendor/swiftmailer/swiftmailer/lib/swift_required.php';
}

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
    'Flarum\Core\DatabaseServiceProvider',
    'Flarum\Core\Settings\SettingsServiceProvider',
    'Flarum\Locale\LocaleServiceProvider',

    'Illuminate\Bus\BusServiceProvider',
    'Illuminate\Filesystem\FilesystemServiceProvider',
    'Illuminate\Hashing\HashServiceProvider',
    'Illuminate\Mail\MailServiceProvider',
    'Illuminate\View\ViewServiceProvider',
    'Illuminate\Events\EventServiceProvider',
    'Illuminate\Validation\ValidationServiceProvider',
];

if (Core::isInstalled()) {
    $serviceProviders[] = 'Flarum\Core\CoreServiceProvider';
}

foreach ($serviceProviders as $provider) {
    $app->register(new $provider($app));
}

if (Core::isInstalled()) {
    $config->set('mail.driver', Core::config('mail_driver'));
    $config->set('mail.host', Core::config('mail_host'));
    $config->set('mail.port', Core::config('mail_port'));
    $config->set('mail.from.address', Core::config('mail_from'));
    $config->set('mail.from.name', Core::config('forum_title'));
    $config->set('mail.encryption', Core::config('mail_encryption'));
    $config->set('mail.username', Core::config('mail_username'));
    $config->set('mail.password', Core::config('mail_password'));

    // Register extensions and tell them to listen for events
    $app->register(new \Flarum\Support\ExtensionsServiceProvider($app));
}

// BootProviders
$app->boot();

return $app;
