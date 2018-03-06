<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Forum;

use Flarum\Event\ConfigureForumRoutes;
use Flarum\Event\ExtensionWasDisabled;
use Flarum\Event\ExtensionWasEnabled;
use Flarum\Event\SettingWasSet;
use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Http\Handler\RouteHandlerFactory;
use Flarum\Http\RouteCollection;

class ForumServiceProvider extends AbstractServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function register()
    {
        $this->app->singleton(UrlGenerator::class, function () {
            return new UrlGenerator($this->app, $this->app->make('flarum.forum.routes'));
        });

        $this->app->singleton('flarum.forum.routes', function () {
            return new RouteCollection;
        });
    }

    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        $this->populateRoutes($this->app->make('flarum.forum.routes'));

        $this->loadViewsFrom(__DIR__.'/../../views', 'flarum.forum');

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
            '/all',
            'index',
            $toDefaultController = $route->toController(Controller\IndexController::class)
        );

        $routes->get(
            '/d/{id:\d+(?:-[^/]*)?}[/{near:[^/]*}]',
            'discussion',
            $route->toController(Controller\DiscussionController::class)
        );

        $routes->get(
            '/u/{username}[/{filter:[^/]*}]',
            'user',
            $route->toController(Controller\WebAppController::class)
        );

        $routes->get(
            '/settings',
            'settings',
            $route->toController(Controller\AuthorizedWebAppController::class)
        );

        $routes->get(
            '/notifications',
            'notifications',
            $route->toController(Controller\AuthorizedWebAppController::class)
        );

        $routes->get(
            '/logout',
            'logout',
            $route->toController(Controller\LogOutController::class)
        );

        $routes->post(
            '/login',
            'login',
            $route->toController(Controller\LogInController::class)
        );

        $routes->post(
            '/register',
            'register',
            $route->toController(Controller\RegisterController::class)
        );

        $routes->get(
            '/confirm/{token}',
            'confirmEmail',
            $route->toController(Controller\ConfirmEmailController::class)
        );

        $routes->get(
            '/reset/{token}',
            'resetPassword',
            $route->toController(Controller\ResetPasswordController::class)
        );

        $routes->post(
            '/reset',
            'savePassword',
            $route->toController(Controller\SavePasswordController::class)
        );

        $this->app->make('events')->fire(
            new ConfigureForumRoutes($routes, $route)
        );

        $defaultRoute = $this->app->make('flarum.settings')->get('default_route');

        if (isset($routes->getRouteData()[0]['GET'][$defaultRoute])) {
            $toDefaultController = $routes->getRouteData()[0]['GET'][$defaultRoute];
        }

        $routes->get(
            '/',
            'default',
            $toDefaultController
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
