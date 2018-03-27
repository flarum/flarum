<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Extension;

use Flarum\Foundation\AbstractServiceProvider;

class ExtensionServiceProvider extends AbstractServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function register()
    {
        $this->app->bind('flarum.extensions', 'Flarum\Extension\ExtensionManager');

        $bootstrappers = $this->app->make('flarum.extensions')->getEnabledBootstrappers();

        foreach ($bootstrappers as $file) {
            $bootstrapper = require $file;

            $this->app->call($bootstrapper);
        }
    }
}
