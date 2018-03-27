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

use Flarum\Asset\JsCompiler;
use Flarum\Asset\LessCompiler;
use Flarum\Foundation\Application;
use Flarum\Locale\JsCompiler as LocaleJsCompiler;
use Flarum\Locale\LocaleManager;
use Illuminate\Contracts\Cache\Repository;

class WebAppAssets
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var Application
     */
    protected $app;

    /**
     * @var Repository
     */
    protected $cache;

    /**
     * @var LocaleManager
     */
    protected $locales;

    /**
     * @param string $name
     * @param Application $app
     * @param Repository $cache
     * @param LocaleManager $locales
     */
    public function __construct($name, Application $app, Repository $cache, LocaleManager $locales)
    {
        $this->name = $name;
        $this->app = $app;
        $this->cache = $cache;
        $this->locales = $locales;
    }

    public function flush()
    {
        $this->flushJs();
        $this->flushCss();
    }

    public function flushJs()
    {
        $this->getJs()->flush();
        $this->flushLocaleJs();
    }

    public function flushLocaleJs()
    {
        foreach ($this->locales->getLocales() as $locale => $info) {
            $this->getLocaleJs($locale)->flush();
        }
    }

    public function flushCss()
    {
        $this->getCss()->flush();
        $this->flushLocaleCss();
    }

    public function flushLocaleCss()
    {
        foreach ($this->locales->getLocales() as $locale => $info) {
            $this->getLocaleCss($locale)->flush();
        }
    }

    /**
     * @return JsCompiler
     */
    public function getJs()
    {
        return new JsCompiler(
            $this->getDestination(),
            "$this->name.js",
            $this->shouldWatch(),
            $this->cache
        );
    }

    /**
     * @return LessCompiler
     */
    public function getCss()
    {
        return new LessCompiler(
            $this->getDestination(),
            "$this->name.css",
            $this->shouldWatch(),
            $this->getLessStorage()
        );
    }

    /**
     * @param $locale
     * @return LocaleJsCompiler
     */
    public function getLocaleJs($locale)
    {
        return new LocaleJsCompiler(
            $this->getDestination(),
            "$this->name-$locale.js",
            $this->shouldWatch(),
            $this->cache
        );
    }

    /**
     * @param $locale
     * @return LessCompiler
     */
    public function getLocaleCss($locale)
    {
        return new LessCompiler(
            $this->getDestination(),
            "$this->name-$locale.css",
            $this->shouldWatch(),
            $this->getLessStorage()
        );
    }

    protected function getDestination()
    {
        return $this->app->publicPath().'/assets';
    }

    protected function shouldWatch()
    {
        return $this->app->config('debug');
    }

    protected function getLessStorage()
    {
        return $this->app->storagePath().'/less';
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }
}
