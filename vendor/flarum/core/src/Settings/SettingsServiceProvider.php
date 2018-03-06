<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Settings;

use Flarum\Foundation\AbstractServiceProvider;

class SettingsServiceProvider extends AbstractServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function register()
    {
        $this->app->singleton('Flarum\Settings\SettingsRepositoryInterface', function () {
            return new MemoryCacheSettingsRepository(
                new DatabaseSettingsRepository(
                    $this->app->make('Illuminate\Database\ConnectionInterface')
                )
            );
        });

        $this->app->alias('Flarum\Settings\SettingsRepositoryInterface', 'flarum.settings');
    }
}
