<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Formatter;

use Flarum\Event\ConfigureFormatter;
use Flarum\Event\ConfigureFormatterParser;
use Flarum\Event\ConfigureFormatterRenderer;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Events\Dispatcher;
use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Unparser;

class Formatter
{
    /**
     * @var Repository
     */
    protected $cache;

    /**
     * @var Dispatcher
     */
    protected $events;

    /**
     * @var string
     */
    protected $cacheDir;

    /**
     * @param Repository $cache
     * @param Dispatcher $events
     * @param string $cacheDir
     */
    public function __construct(Repository $cache, Dispatcher $events, $cacheDir)
    {
        $this->cache = $cache;
        $this->events = $events;
        $this->cacheDir = $cacheDir;
    }

    /**
     * Parse text.
     *
     * @param string $text
     * @param mixed $context
     * @return string
     */
    public function parse($text, $context = null)
    {
        $parser = $this->getParser($context);

        $this->events->fire(new ConfigureFormatterParser($parser, $context, $text));

        return $parser->parse($text);
    }

    /**
     * Render parsed XML.
     *
     * @param string $xml
     * @param mixed $context
     * @return string
     */
    public function render($xml, $context = null)
    {
        $renderer = $this->getRenderer($context);

        $this->events->fire(new ConfigureFormatterRenderer($renderer, $context, $xml));

        return $renderer->render($xml);
    }

    /**
     * Unparse XML.
     *
     * @param string $xml
     * @return string
     */
    public function unparse($xml)
    {
        return Unparser::unparse($xml);
    }

    /**
     * Flush the cache so that the formatter components are regenerated.
     */
    public function flush()
    {
        $this->cache->forget('flarum.formatter.parser');
        $this->cache->forget('flarum.formatter.renderer');
    }

    /**
     * @return Configurator
     */
    protected function getConfigurator()
    {
        $configurator = new Configurator;

        $configurator->rootRules->enableAutoLineBreaks();

        $configurator->rendering->engine = 'PHP';
        $configurator->rendering->engine->cacheDir = $this->cacheDir;

        $configurator->Escaper;
        $configurator->Autoemail;
        $configurator->Autolink;
        $configurator->tags->onDuplicate('replace');

        $this->events->fire(new ConfigureFormatter($configurator));

        $this->configureExternalLinks($configurator);

        return $configurator;
    }

    /**
     * @param Configurator $configurator
     */
    protected function configureExternalLinks(Configurator $configurator)
    {
        $dom = $configurator->tags['URL']->template->asDOM();

        foreach ($dom->getElementsByTagName('a') as $a) {
            $a->setAttribute('target', '_blank');
            $a->setAttribute('rel', 'nofollow');
        }

        $dom->saveChanges();
    }

    /**
     * Get a TextFormatter component.
     *
     * @param string $name "renderer" or "parser"
     * @return mixed
     */
    protected function getComponent($name)
    {
        $cacheKey = 'flarum.formatter.'.$name;

        return $this->cache->rememberForever($cacheKey, function () use ($name) {
            return $this->getConfigurator()->finalize()[$name];
        });
    }

    /**
     * Get the parser.
     *
     * @param mixed $context
     * @return \s9e\TextFormatter\Parser
     */
    protected function getParser($context = null)
    {
        $parser = $this->getComponent('parser');

        $parser->registeredVars['context'] = $context;

        return $parser;
    }

    /**
     * Get the renderer.
     *
     * @param mixed $context
     * @return \s9e\TextFormatter\Renderer
     */
    protected function getRenderer($context = null)
    {
        spl_autoload_register(function ($class) {
            if (file_exists($file = $this->cacheDir.'/'.$class.'.php')) {
                include $file;
            }
        });

        return $this->getComponent('renderer');
    }

    /**
     * Get the formatter JavaScript.
     *
     * @return string
     */
    public function getJs()
    {
        $configurator = $this->getConfigurator();
        $configurator->enableJavaScript();
        $configurator->javascript->exportMethods = ['preview'];

        return $configurator->finalize([
            'returnParser' => false,
            'returnRenderer' => false
        ])['js'];
    }
}
