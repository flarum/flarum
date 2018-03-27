<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Admin;

use Flarum\Event\ExtensionWasDisabled;
use Flarum\Event\ExtensionWasEnabled;
use Flarum\Event\SettingWasSet;
use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Http\Handler\RouteHandlerFactory;
use Flarum\Http\RouteCollection;

class AdminServiceProvider extends AbstractServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function register()
    {
        $this->app->singleton(UrlGenerator::class, function () {
            return new UrlGenerator($this->app, $this->app->make('flarum.admin.routes'));
        });

        $this->app->singleton('flarum.admin.routes', function () {
            return new RouteCollection;
        });
    }

    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        $this->populateRoutes($this->app->make('flarum.admin.routes'));

        $this->loadViewsFrom(__DIR__.'/../../views', 'flarum.admin');

        $this->flushWebAppAssetsWhenThemeChanged();

        $this->flushWebAppAssetsWhenExtensionsChanged();
    }

    /**
     * Populate the forum client routes.
     *
     * @param RouteCollection $routes
     */
    protected function populateRoutes(RouteCollection $routes)
    {
        $route = $this->app->make(RouteHandlerFactory::class);

        $routes->get(
            '/',
            'index',
            $route->toController(Controller\WebAppController::class)
        );
    }

    protected function flushWebAppAssetsWhenThemeChanged()
    {
        $this->app->make('events')->listen(SettingWasSet::class, function (SettingWasSet $event) {
            if (preg_match('/^theme_|^custom_less$/i', $event->key)) {
                $this->getWebAppAssets()->flushCss();
            }
        });
    }

    protected function flushWebAppAssetsWhenExtensionsChanged()
    {
        $events = $this->app->make('events');

        $events->listen(ExtensionWasEnabled::class, [$this, 'flushWebAppAssets']);
        $events->listen(ExtensionWasDisabled::class, [$this, 'flushWebAppAssets']);
    }

    public function flushWebAppAssets()
    {
        $this->getWebAppAssets()->flush();
    }

    /**
     * @return \Flarum\Http\WebApp\WebAppAssets
     */
    protected function getWebAppAssets()
    {
        return $this->app->make(WebApp::class)->getAssets();
    }
}
