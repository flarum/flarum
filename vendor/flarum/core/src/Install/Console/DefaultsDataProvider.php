<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Install\Console;

class DefaultsDataProvider implements DataProviderInterface
{
    protected $databaseConfiguration = [
        'driver'   => 'mysql',
        'host'     => 'localhost',
        'database' => 'flarum',
        'username' => 'root',
        'password' => 'root',
        'prefix'   => '',
        'port'     => '3306',
    ];

    protected $baseUrl = 'http://flarum.dev';

    protected $adminUser = [
        'username'              => 'admin',
        'password'              => 'password',
        'password_confirmation' => 'password',
        'email'                 => 'admin@example.com',
    ];

    protected $settings = [
        'allow_post_editing' => 'reply',
        'allow_renaming' => '10',
        'allow_sign_up' => '1',
        'custom_less' => '',
        'default_locale' => 'en',
        'default_route' => '/all',
        'extensions_enabled' => '[]',
        'forum_title' => 'Development Forum',
        'forum_description' => '',
        'mail_driver' => 'mail',
        'mail_from' => 'noreply@flarum.dev',
        'theme_colored_header' => '0',
        'theme_dark_mode' => '0',
        'theme_primary_color' => '#4D698E',
        'theme_secondary_color' => '#4D698E',
        'welcome_message' => 'This is beta software and you should not use it in production.',
        'welcome_title' => 'Welcome to Development Forum',
    ];

    public function getDatabaseConfiguration()
    {
        return $this->databaseConfiguration;
    }

    public function setDatabaseConfiguration(array $databaseConfiguration)
    {
        $this->databaseConfiguration = $databaseConfiguration;
    }

    public function getBaseUrl()
    {
        return $this->baseUrl;
    }

    public function setBaseUrl($baseUrl)
    {
        $this->baseUrl = $baseUrl;
    }

    public function getAdminUser()
    {
        return $this->adminUser;
    }

    public function setAdminUser(array $adminUser)
    {
        $this->adminUser = $adminUser;
    }

    public function getSettings()
    {
        return $this->settings;
    }

    public function setSettings(array $settings)
    {
        $this->settings = $settings;
    }

    public function setSetting($key, $value)
    {
        $this->settings[$key] = $value;
    }
}
