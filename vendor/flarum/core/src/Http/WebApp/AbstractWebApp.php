<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Http\WebApp;

use Flarum\Locale\LocaleManager;
use Flarum\Settings\SettingsRepositoryInterface;

abstract class AbstractWebApp
{
    /**
     * @var WebAppAssetsFactory
     */
    protected $assets;

    /**
     * @var WebAppViewFactory
     */
    protected $view;

    /**
     * @var SettingsRepositoryInterface
     */
    protected $settings;

    /**
     * @var LocaleManager
     */
    protected $locales;

    /**
     * @param WebAppAssetsFactory $assets
     * @param WebAppViewFactory $view
     * @param SettingsRepositoryInterface $settings
     * @param LocaleManager $locales
     */
    public function __construct(WebAppAssetsFactory $assets, WebAppViewFactory $view, SettingsRepositoryInterface $settings, LocaleManager $locales)
    {
        $this->assets = $assets;
        $this->view = $view;
        $this->settings = $settings;
        $this->locales = $locales;
    }

    /**
     * @return WebAppView
     */
    public function getView()
    {
        $view = $this->view->make($this->getLayout(), $this->getAssets());

        $this->addDefaultAssets($view);
        $this->addCustomLess($view);
        $this->addTranslations($view);

        return $view;
    }

    /**
     * @return WebAppAssets
     */
    public function getAssets()
    {
        return $this->assets->make($this->getName());
    }

    /**
     * Get the name of the client.
     *
     * @return string
     */
    abstract protected function getName();

    /**
     * Get the path to the client layout view.
     *
     * @return string
     */
    protected function getLayout()
    {
        return __DIR__.'/../../../views/'.$this->getName().'.blade.php';
    }

    /**
     * Get a regular expression to match against translation keys.
     *
     * @return string
     */
    protected function getTranslationFilter()
    {
        return '/^.+(?:\.|::)(?:'.$this->getName().'|lib)\./';
    }

    /**
     * @param WebAppView $view
     */
    private function addDefaultAssets(WebAppView $view)
    {
        $root = __DIR__.'/../../..';
        $name = $this->getName();

        $view->getJs()->addFile("$root/js/$name/dist/app.js");
        $view->getCss()->addFile("$root/less/$name/app.less");
    }

    /**
     * @param WebAppView $view
     */
    private function addCustomLess(WebAppView $view)
    {
        $css = $view->getCss();
        $localeCss = $view->getLocaleCss();

        $lessVariables = function () {
            $less = '';

            foreach ($this->getLessVariables() as $name => $value) {
                $less .= "@$name: $value;";
            }

            return $less;
        };

        $css->addString($lessVariables);
        $localeCss->addString($lessVariables);

        $css->addString(function () {
            return $this->settings->get('custom_less');
        });
    }

    /**
     * Get the values of any LESS variables to compile into the CSS, based on
     * the forum's configuration.
     *
     * @return array
     */
    private function getLessVariables()
    {
        return [
            'config-primary-color'   => $this->settings->get('theme_primary_color') ?: '#000',
            'config-secondary-color' => $this->settings->get('theme_secondary_color') ?: '#000',
            'config-dark-mode'       => $this->settings->get('theme_dark_mode') ? 'true' : 'false',
            'config-colored-header'  => $this->settings->get('theme_colored_header') ? 'true' : 'false'
        ];
    }

    /**
     * @param WebAppView $view
     */
    private function addTranslations(WebAppView $view)
    {
        $translations = array_get($this->locales->getTranslator()->getMessages(), 'messages', []);

        $translations = $this->filterTranslations($translations);

        $view->getLocaleJs()->setTranslations($translations);
    }

    /**
     * Take a selection of keys from a collection of translations.
     *
     * @param array $translations
     * @return array
     */
    private function filterTranslations(array $translations)
    {
        $filter = $this->getTranslationFilter();

        if (! $filter) {
            return [];
        }

        $filtered = array_filter(array_keys($translations), function ($id) use ($filter) {
            return preg_match($filter, $id);
        });

        return array_only($translations, $filtered);
    }
}
