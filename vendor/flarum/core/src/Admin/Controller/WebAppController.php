<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Admin\Controller;

use Flarum\Admin\WebApp;
use Flarum\Core\Permission;
use Flarum\Event\PrepareUnserializedSettings;
use Flarum\Extension\ExtensionManager;
use Flarum\Http\Controller\AbstractWebAppController;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Events\Dispatcher;
use Psr\Http\Message\ServerRequestInterface;

class WebAppController extends AbstractWebAppController
{
    /**
     * @var SettingsRepositoryInterface
     */
    protected $settings;

    /**
     * @var ExtensionManager
     */
    protected $extensions;

    /**
     * @param WebApp $webApp
     * @param Dispatcher $events
     * @param SettingsRepositoryInterface $settings
     * @param ExtensionManager $extensions
     */
    public function __construct(WebApp $webApp, Dispatcher $events, SettingsRepositoryInterface $settings, ExtensionManager $extensions)
    {
        $this->webApp = $webApp;
        $this->events = $events;
        $this->settings = $settings;
        $this->extensions = $extensions;
    }

    /**
     * {@inheritdoc}
     */
    protected function getView(ServerRequestInterface $request)
    {
        $view = parent::getView($request);

        $settings = $this->settings->all();

        $this->events->fire(
            new PrepareUnserializedSettings($settings)
        );

        $view->setVariable('settings', $settings);
        $view->setVariable('permissions', Permission::map());
        $view->setVariable('extensions', $this->extensions->getExtensions()->toArray());

        return $view;
    }
}
