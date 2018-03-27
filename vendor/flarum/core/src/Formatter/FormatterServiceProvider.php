<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Formatter;

use Flarum\Event\ExtensionWasDisabled;
use Flarum\Event\ExtensionWasEnabled;
use Flarum\Foundation\AbstractServiceProvider;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;

class FormatterServiceProvider extends AbstractServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function boot(Dispatcher $events)
    {
        $events->listen(ExtensionWasEnabled::class, [$this, 'flushFormatter']);
        $events->listen(ExtensionWasDisabled::class, [$this, 'flushFormatter']);
    }

    /**
     * {@inheritdoc}
     */
    public function register()
    {
        $this->app->singleton('flarum.formatter', function (Container $container) {
            return new Formatter(
                $container->make('cache.store'),
                $container->make('events'),
                $this->app->storagePath().'/formatter'
            );
        });

        $this->app->alias('flarum.formatter', 'Flarum\Formatter\Formatter');
    }

    public function flushFormatter()
    {
        $this->app->make('flarum.formatter')->flush();
    }
}
