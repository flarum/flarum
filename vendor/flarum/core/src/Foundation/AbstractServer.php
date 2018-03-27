<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Foundation;

use Illuminate\Config\Repository as ConfigRepository;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

abstract class AbstractServer
{
    /**
     * @var Application
     */
    protected $app;

    /**
     * @var string
     */
    protected $basePath;

    /**
     * @var string
     */
    protected $publicPath;

    /**
     * @var string
     */
    protected $storagePath;

    /**
     * @var array
     */
    protected $config;

    /**
     * @var callable[]
     */
    protected $extendCallbacks = [];

    /**
     * @param null $basePath
     * @param null $publicPath
     */
    public function __construct($basePath = null, $publicPath = null)
    {
        if ($basePath === null) {
            $basePath = getcwd();
        }

        if ($publicPath === null) {
            $publicPath = $basePath;
        }

        $this->basePath = $basePath;
        $this->publicPath = $publicPath;

        if (file_exists($file = $this->basePath.'/config.php')) {
            $this->config = include $file;
        }
    }

    /**
     * @return string
     */
    public function getBasePath()
    {
        return $this->basePath;
    }

    /**
     * @return string
     */
    public function getPublicPath()
    {
        return $this->publicPath;
    }

    /**
     * @return string
     */
    public function getStoragePath()
    {
        return $this->storagePath;
    }

    /**
     * @param $basePath
     */
    public function setBasePath($basePath)
    {
        $this->basePath = $basePath;
    }

    /**
     * @param $publicPath
     */
    public function setPublicPath($publicPath)
    {
        $this->publicPath = $publicPath;
    }

    /**
     * @param $storagePath
     */
    public function setStoragePath($storagePath)
    {
        $this->storagePath = $storagePath;
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @param array $config
     */
    public function setConfig(array $config)
    {
        $this->config = $config;
    }

    /**
     * @param callable $callback
     */
    public function extend(callable $callback)
    {
        $this->extendCallbacks[] = $callback;
    }

    /**
     * @return Application
     */
    public function getApp()
    {
        if ($this->app !== null) {
            return $this->app;
        }

        date_default_timezone_set('UTC');

        $app = new Application($this->basePath, $this->publicPath);

        if ($this->storagePath) {
            $app->useStoragePath($this->storagePath);
        }

        $app->instance('env', 'production');
        $app->instance('flarum.config', $this->config);
        $app->instance('config', $config = $this->getIlluminateConfig($app));

        $this->registerLogger($app);

        $this->registerCache($app);

        $app->register('Flarum\Database\DatabaseServiceProvider');
        $app->register('Flarum\Settings\SettingsServiceProvider');
        $app->register('Flarum\Locale\LocaleServiceProvider');
        $app->register('Illuminate\Bus\BusServiceProvider');
        $app->register('Illuminate\Filesystem\FilesystemServiceProvider');
        $app->register('Illuminate\Hashing\HashServiceProvider');
        $app->register('Illuminate\Mail\MailServiceProvider');
        $app->register('Illuminate\View\ViewServiceProvider');
        $app->register('Illuminate\Validation\ValidationServiceProvider');

        if ($app->isInstalled() && $app->isUpToDate()) {
            $settings = $app->make('Flarum\Settings\SettingsRepositoryInterface');

            $config->set('mail.driver', $settings->get('mail_driver'));
            $config->set('mail.host', $settings->get('mail_host'));
            $config->set('mail.port', $settings->get('mail_port'));
            $config->set('mail.from.address', $settings->get('mail_from'));
            $config->set('mail.from.name', $settings->get('forum_title'));
            $config->set('mail.encryption', $settings->get('mail_encryption'));
            $config->set('mail.username', $settings->get('mail_username'));
            $config->set('mail.password', $settings->get('mail_password'));

            $app->register('Flarum\Core\CoreServiceProvider');
            $app->register('Flarum\Api\ApiServiceProvider');
            $app->register('Flarum\Forum\ForumServiceProvider');
            $app->register('Flarum\Admin\AdminServiceProvider');

            foreach ($this->extendCallbacks as $callback) {
                $app->call($callback);
            }

            $app->register('Flarum\Extension\ExtensionServiceProvider');
        }

        $app->boot();

        $this->app = $app;

        return $app;
    }

    /**
     * @param Application $app
     * @return ConfigRepository
     */
    protected function getIlluminateConfig(Application $app)
    {
        return new ConfigRepository([
            'view' => [
                'paths' => [],
                'compiled' => $app->storagePath().'/views',
            ],
            'mail' => [
                'driver' => 'mail',
            ],
            'filesystems' => [
                'default' => 'local',
                'cloud' => 's3',
                'disks' => [
                    'flarum-avatars' => [
                        'driver' => 'local',
                        'root'   => $app->publicPath().'/assets/avatars'
                    ]
                ]
            ]
        ]);
    }

    /**
     * @param Application $app
     */
    protected function registerLogger(Application $app)
    {
        $logger = new Logger($app->environment());
        $logPath = $app->storagePath().'/logs/flarum.log';

        $handler = new StreamHandler($logPath, Logger::DEBUG);
        $handler->setFormatter(new LineFormatter(null, null, true, true));

        $logger->pushHandler($handler);

        $app->instance('log', $logger);
        $app->alias('log', 'Psr\Log\LoggerInterface');
    }

    /**
     * @param Application $app
     */
    protected function registerCache(Application $app)
    {
        $app->singleton('cache.store', function ($app) {
            return new \Illuminate\Cache\Repository($app->make('cache.filestore'));
        });

        $app->singleton('cache.filestore', function ($app) {
            return new \Illuminate\Cache\FileStore(
                new \Illuminate\Filesystem\Filesystem(),
                $app->storagePath().'/cache'
            );
        });

        $app->alias('cache.filestore', 'Illuminate\Contracts\Cache\Store');
        $app->alias('cache.store', 'Illuminate\Contracts\Cache\Repository');
    }
}
