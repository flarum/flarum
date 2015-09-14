<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Flarum\Core;
use Flarum\Core\Application;
use Illuminate\Cache\FileStore;
use Illuminate\Cache\Repository;
use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Filesystem\Filesystem;

define('FLARUM_START', microtime(true));

require __DIR__ . '/vendor/autoload.php';

// franzliedke/studio currently doesn't autoload files (see issue below), so we
// will need to load them manually if we're using studio.
// https://github.com/franzliedke/studio/issues/29
if (file_exists(__DIR__ . '/core')) {
    require __DIR__ . '/core/src/helpers.php';
    require __DIR__ . '/core/vendor/swiftmailer/swiftmailer/lib/swift_required.php';
}

$app = new Application(realpath(__DIR__));
$app->instance('path.public', __DIR__.'/..');

Illuminate\Container\Container::setInstance($app);

if (file_exists($configFile = __DIR__.'/../config.php')) {
    $app->instance('flarum.config', include $configFile);
}

date_default_timezone_set('UTC');

$app->instance('config', $config = new ConfigRepository([
    'view' => [
        'paths' => [
            realpath(base_path('resources/views'))
        ],
        'compiled' => realpath(storage_path().'/framework/views'),
    ],
    'mail' => [
        'driver' => 'mail',
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

$logger = new Monolog\Logger($app->environment());
$logPath = $app->storagePath() . '/logs/flarum.log';
$handler = new \Monolog\Handler\StreamHandler($logPath, Monolog\Logger::DEBUG);
$handler->setFormatter(new \Monolog\Formatter\LineFormatter(null, null, true, true));
$logger->pushHandler($handler);

$app->instance('log', $logger);
$app->alias('log', 'Psr\Log\LoggerInterface');

$app->singleton('cache', function ($app) {
    $store = new FileStore(new Filesystem(), storage_path('framework/cache'));
    $repository = new Repository($store);
    $repository->setEventDispatcher($app->make('events'));
    return $repository;
});
$app->alias('cache', 'Illuminate\Contracts\Cache\Repository');

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

foreach ($serviceProviders as $provider) {
    $app->register(new $provider($app));
}

if (Core::isInstalled()) {
    $settings = $app->make('Flarum\Core\Settings\SettingsRepository');

    $app->register(new \Flarum\Core\CoreServiceProvider($app));

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

$app->boot();

// If the version stored in the database doesn't match the version of the
// code, then run the upgrade script (migrations). This is temporary - a
// proper, more secure upgrade method is planned.
if (Core::isInstalled() && $settings->get('version') !== $app::VERSION) {
    $input = new \Symfony\Component\Console\Input\StringInput('');
    $output = new \Symfony\Component\Console\Output\BufferedOutput;

    app('Flarum\Console\UpgradeCommand')->run($input, $output);

    $settings->set('version', $app::VERSION);

    app('flarum.formatter')->flush();

    $forum = app('Flarum\Forum\Actions\ClientAction');
    $forum->flushAssets();

    $admin = app('Flarum\Admin\Actions\ClientAction');
    $admin->flushAssets();
}

return $app;
