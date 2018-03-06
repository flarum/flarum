<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Database;

use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Foundation\Application;
use Illuminate\Database\ConnectionResolver;
use Illuminate\Database\Connectors\ConnectionFactory;
use PDO;

class DatabaseServiceProvider extends AbstractServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function register()
    {
        $this->app->singleton('flarum.db', function () {
            $factory = new ConnectionFactory($this->app);

            $connection = $factory->make($this->app->config('database'));
            $connection->setEventDispatcher($this->app->make('Illuminate\Contracts\Events\Dispatcher'));
            $connection->setFetchMode(PDO::FETCH_CLASS);

            return $connection;
        });

        $this->app->alias('flarum.db', 'Illuminate\Database\ConnectionInterface');

        $this->app->singleton('Illuminate\Database\ConnectionResolverInterface', function () {
            $resolver = new ConnectionResolver([
                'flarum' => $this->app->make('flarum.db'),
            ]);
            $resolver->setDefaultConnection('flarum');

            return $resolver;
        });

        $this->app->alias('Illuminate\Database\ConnectionResolverInterface', 'db');

        $this->app->singleton('Flarum\Database\MigrationRepositoryInterface', function ($app) {
            return new DatabaseMigrationRepository($app['db'], 'migrations');
        });

        $this->app->bind(MigrationCreator::class, function (Application $app) {
            return new MigrationCreator($app->make('Illuminate\Filesystem\Filesystem'), $app->basePath());
        });
    }

    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        if ($this->app->isInstalled()) {
            AbstractModel::setConnectionResolver($this->app->make('Illuminate\Database\ConnectionResolverInterface'));
            AbstractModel::setEventDispatcher($this->app->make('events'));
        }
    }
}
