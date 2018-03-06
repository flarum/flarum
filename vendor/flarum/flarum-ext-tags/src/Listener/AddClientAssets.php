<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Tags\Listener;

use Flarum\Event\ConfigureForumRoutes;
use Flarum\Event\ConfigureWebApp;
use Illuminate\Contracts\Events\Dispatcher;

class AddClientAssets
{
    /**
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events)
    {
        $events->listen(ConfigureWebApp::class, [$this, 'addAssets']);
        $events->listen(ConfigureForumRoutes::class, [$this, 'addRoutes']);
    }

    /**
     * @param ConfigureWebApp $app
     */
    public function addAssets(ConfigureWebApp $app)
    {
        if ($app->isForum()) {
            $app->addAssets([
                __DIR__.'/../../js/forum/dist/extension.js',
                __DIR__.'/../../less/forum/extension.less'
            ]);
            $app->addBootstrapper('flarum/tags/main');
        }

        if ($app->isAdmin()) {
            $app->addAssets([
                __DIR__.'/../../js/admin/dist/extension.js',
                __DIR__.'/../../less/admin/extension.less'
            ]);
            $app->addBootstrapper('flarum/tags/main');
        }
    }

    /**
     * @param ConfigureForumRoutes $routes
     */
    public function addRoutes(ConfigureForumRoutes $routes)
    {
        $routes->get('/t/{slug}', 'tag');
        $routes->get('/tags', 'tags');
    }
}
