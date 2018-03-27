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

use Symfony\Component\Translation\Translator as SymfonyTranslator;

class LocaleManager
{
    /**
     * @var SymfonyTranslator
     */
    protected $translator;

    protected $locales = [];

    protected $js = [];

    protected $css = [];

    /**
     * @param SymfonyTranslator $translator
     */
    public function __construct(SymfonyTranslator $translator)
    {
        $this->translator = $translator;
    }

    public function getLocale()
    {
        return $this->translator->getLocale();
    }

    public function setLocale($locale)
    {
        $this->translator->setLocale($locale);
    }

    public function addLocale($locale, $name)
    {
        $this->locales[$locale] = $name;
    }

    public function getLocales()
    {
        return $this->locales;
    }

    public function hasLocale($locale)
    {
        return isset($this->locales[$locale]);
    }

    public function addTranslations($locale, $file, $module = null)
    {
        $prefix = $module ? $module.'::' : '';

        $this->translator->addResource('prefixed_yaml', compact('file', 'prefix'), $locale);
    }

    public function addJsFile($locale, $js)
    {
        $this->js[$locale][] = $js;
    }

    public function getJsFiles($locale)
    {
        $files = array_get($this->js, $locale, []);

        $parts = explode('-', $locale);

        if (count($parts) > 1) {
            $files = array_merge(array_get($this->js, $parts[0], []), $files);
        }

        return $files;
    }

    public function addCssFile($locale, $css)
    {
        $this->css[$locale][] = $css;
    }

    public function getCssFiles($locale)
    {
        $files = array_get($this->css, $locale, []);

        $parts = explode('-', $locale);

        if (count($parts) > 1) {
            $files = array_merge(array_get($this->css, $parts[0], []), $files);
        }

        return $files;
    }

    /**
     * @return SymfonyTranslator
     */
    public function getTranslator()
    {
        return $this->translator;
    }

    /**
     * @param SymfonyTranslator $translator
     */
    public function setTranslator(SymfonyTranslator $translator)
    {
        $this->translator = $translator;
    }
}
