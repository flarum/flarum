<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Asset;

use Exception;
use Illuminate\Cache\Repository;
use MatthiasMullie\Minify;
use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Configurator\JavaScript\Minifiers\FirstAvailable;

class JsCompiler extends RevisionCompiler
{
    /**
     * @var Repository
     */
    protected $cache;

    /**
     * @param string $path
     * @param string $filename
     * @param bool $watch
     * @param Repository $cache
     */
    public function __construct($path, $filename, $watch = false, Repository $cache = null)
    {
        parent::__construct($path, $filename, $watch);

        $this->cache = $cache;
    }

    /**
     * {@inheritdoc}
     */
    protected function format($string)
    {
        if (! $this->watch) {
            $key = 'js.'.sha1($string);

            $string = $this->cache->rememberForever($key, function () use ($string) {
                return $this->minify($string);
            });
        }

        return $string.";\n";
    }

    /**
     * {@inheritdoc}
     */
    protected function getCacheDifferentiator()
    {
        return $this->watch;
    }

    /**
     * @param string $source
     * @return string
     */
    protected function minify($source)
    {
        set_time_limit(60);

        try {
            $source = $this->minifyWithClosureCompilerService($source);
        } catch (Exception $e) {
            $source = $this->minifyWithFallback($source);
        }

        return $source;
    }

    /**
     * @param string $source
     * @return string
     */
    protected function minifyWithClosureCompilerService($source)
    {
        // The minifier may need some classes bundled with the Configurator so we autoload it
        class_exists(Configurator::class);

        $minifier = new FirstAvailable;

        $remoteCache = $minifier->add('RemoteCache');
        $remoteCache->url = 'http://s9e-textformatter.rhcloud.com/flarum-minifier/';

        $hostedMinifer = $minifier->add('HostedMinifier');
        $hostedMinifer->url = 'http://s9e-textformatter.rhcloud.com/flarum-minifier/';
        $hostedMinifer->httpClient->timeout = 30;

        $ccs = $minifier->add('ClosureCompilerService');
        $ccs->compilationLevel = 'SIMPLE_OPTIMIZATIONS';
        $ccs->httpClient->timeout = 30;

        $minifier->add('MatthiasMullieMinify');

        return $minifier->minify($source);
    }

    /**
     * @param string $source
     * @return string
     */
    protected function minifyWithFallback($source)
    {
        $minifier = new Minify\JS($source);

        return $minifier->minify();
    }
}
