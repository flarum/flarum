<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Locale;

use Flarum\Event\ConfigureLocales;
use Flarum\Foundation\AbstractServiceProvider;
use Illuminate\Contracts\Events\Dispatcher;
use Symfony\Component\Translation\MessageSelector;

class LocaleServiceProvider extends AbstractServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function boot(Dispatcher $events)
    {
        $locales = $this->app->make('flarum.localeManager');

        $locales->addLocale($this->getDefaultLocale(), 'Default');

        $events->fire(new ConfigureLocales($locales));
    }

    /**
     * {@inheritdoc}
     */
    public function register()
    {
        $this->app->singleton('Flarum\Locale\LocaleManager');
        $this->app->alias('Flarum\Locale\LocaleManager', 'flarum.localeManager');

        $this->app->singleton('translator', function () {
            $defaultLocale = $this->getDefaultLocale();

            $translator = new Translator($defaultLocale, new MessageSelector());
            $translator->setFallbackLocales([$defaultLocale, 'en']);
            $translator->addLoader('prefixed_yaml', new PrefixedYamlFileLoader());

            return $translator;
        });
        $this->app->alias('translator', 'Symfony\Component\Translation\Translator');
        $this->app->alias('translator', 'Symfony\Component\Translation\TranslatorInterface');
    }

    private function getDefaultLocale()
    {
        return $this->app->isInstalled() && $this->app->isUpToDate()
            ? $this->app->make('flarum.settings')->get('default_locale', 'en')
            : 'en';
    }
}
