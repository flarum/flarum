<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Api\Serializer;

use Flarum\Forum\UrlGenerator;
use Flarum\Foundation\Application;
use Flarum\Settings\SettingsRepositoryInterface;

class ForumSerializer extends AbstractSerializer
{
    /**
     * {@inheritdoc}
     */
    protected $type = 'forums';

    /**
     * @var Application
     */
    protected $app;

    /**
     * @var SettingsRepositoryInterface
     */
    protected $settings;

    /**
     * @var UrlGenerator
     */
    protected $url;

    /**
     * @param Application $app
     * @param SettingsRepositoryInterface $settings
     * @param UrlGenerator $url
     */
    public function __construct(Application $app, SettingsRepositoryInterface $settings, UrlGenerator $url)
    {
        $this->app = $app;
        $this->settings = $settings;
        $this->url = $url;
    }

    /**
     * {@inheritdoc}
     */
    public function getId($model)
    {
        return 1;
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultAttributes($model)
    {
        $attributes = [
            'title' => $this->settings->get('forum_title'),
            'description' => $this->settings->get('forum_description'),
            'showLanguageSelector' => (bool) $this->settings->get('show_language_selector', true),
            'baseUrl' => $url = $this->app->url(),
            'basePath' => parse_url($url, PHP_URL_PATH) ?: '',
            'debug' => $this->app->inDebugMode(),
            'apiUrl' => $this->app->url('api'),
            'welcomeTitle' => $this->settings->get('welcome_title'),
            'welcomeMessage' => $this->settings->get('welcome_message'),
            'themePrimaryColor' => $this->settings->get('theme_primary_color'),
            'themeSecondaryColor' => $this->settings->get('theme_secondary_color'),
            'logoUrl' => $this->getLogoUrl(),
            'faviconUrl' => $this->getFaviconUrl(),
            'headerHtml' => $this->settings->get('custom_header'),
            'allowSignUp' => (bool) $this->settings->get('allow_sign_up'),
            'defaultRoute'  => $this->settings->get('default_route'),
            'canViewDiscussions' => $this->actor->can('viewDiscussions'),
            'canStartDiscussion' => $this->actor->can('startDiscussion'),
            'canViewUserList' => $this->actor->can('viewUserList')
        ];

        if ($this->actor->can('administrate')) {
            $attributes['adminUrl'] = $this->app->url('admin');
            $attributes['version'] = $this->app->version();
        }

        return $attributes;
    }

    /**
     * @return \Tobscure\JsonApi\Relationship
     */
    protected function groups($model)
    {
        return $this->hasMany($model, 'Flarum\Api\Serializer\GroupSerializer');
    }

    /**
     * @return null|string
     */
    protected function getLogoUrl()
    {
        $logoPath = $this->settings->get('logo_path');

        return $logoPath ? $this->url->toPath('assets/'.$logoPath) : null;
    }

    /**
     * @return null|string
     */
    protected function getFaviconUrl()
    {
        $faviconPath = $this->settings->get('favicon_path');

        return $faviconPath ? $this->url->toPath('assets/'.$faviconPath) : null;
    }
}
