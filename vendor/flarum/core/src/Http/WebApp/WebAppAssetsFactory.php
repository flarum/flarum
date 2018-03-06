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

use Flarum\Foundation\Application;
use Flarum\Locale\LocaleManager;
use Illuminate\Contracts\Cache\Repository;

class WebAppAssetsFactory
{
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
     * @param Application $app
     * @param Repository $cache
     * @param LocaleManager $locales
     */
    public function __construct(Application $app, Repository $cache, LocaleManager $locales)
    {
        $this->app = $app;
        $this->cache = $cache;
        $this->locales = $locales;
    }

    /**
     * @param string $name
     * @return WebAppAssets
     */
    public function make($name)
    {
        return new WebAppAssets($name, $this->app, $this->cache, $this->locales);
    }
}
