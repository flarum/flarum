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

use Flarum\Api\Client;
use Flarum\Api\Serializer\AbstractSerializer;
use Flarum\Asset\CompilerInterface;
use Flarum\Foundation\Application;
use Flarum\Locale\JsCompiler;
use Flarum\Locale\LocaleManager;
use Illuminate\View\Factory;
use Psr\Http\Message\ServerRequestInterface as Request;
use Tobscure\JsonApi\Document;
use Tobscure\JsonApi\Resource;

/**
 * This class represents a view which boots up Flarum's client.
 */
class WebAppView
{
    /**
     * The title of the document, displayed in the <title> tag.
     *
     * @var null|string
     */
    public $title;

    /**
     * The description of the document, displayed in a <meta> tag.
     *
     * @var null|string
     */
    public $description;

    /**
     * The language of the document, displayed as the value of the attribute `dir` in the <html> tag.
     *
     * @var null|string
     */
    public $language;

    /**
     * The text direction of the document, displayed as the value of the attribute `dir` in the <html> tag.
     *
     * @var null|string
     */
    public $direction;

    /**
     * The path to the client layout view to display.
     *
     * @var string
     */
    public $layout;

    /**
     * The SEO content of the page, displayed within the layout in <noscript>
     * tags.
     *
     * @var string
     */
    public $content;

    /**
     * An API response to be preloaded into the page.
     *
     * This should be a JSON-API document.
     *
     * @var null|array|object
     */
    public $document;

    /**
     * Other variables to preload into the page.
     *
     * @var array
     */
    protected $variables = [];

    /**
     * An array of JS modules to load before booting the app.
     *
     * @var array
     */
    protected $modules = ['locale'];

    /**
     * An array of strings to append to the page's <head>.
     *
     * @var array
     */
    protected $head = [];

    /**
     * An array of strings to prepend before the page's </body>.
     *
     * @var array
     */
    protected $foot = [];

    /**
     * A map of <link> tags to be generated.
     *
     * @var array
     */
    protected $links = [];

    /**
     * @var CompilerInterface
     */
    protected $js;

    /**
     * @var CompilerInterface
     */
    protected $css;

    /**
     * @var CompilerInterface
     */
    protected $localeJs;

    /**
     * @var CompilerInterface
     */
    protected $localeCss;

    /**
     * @var WebAppAssets
     */
    protected $assets;

    /**
     * @var Client
     */
    protected $api;

    /**
     * @var Factory
     */
    protected $view;

    /**
     * @var LocaleManager
     */
    protected $locales;

    /**
     * @var AbstractSerializer
     */
    protected $userSerializer;

    /**
     * @var Application
     */
    protected $app;

    /**
     * @param string $layout
     * @param WebAppAssets $assets
     * @param Client $api
     * @param Factory $view
     * @param LocaleManager $locales
     * @param AbstractSerializer $userSerializer
     * @param Application $app
     */
    public function __construct($layout, WebAppAssets $assets, Client $api, Factory $view, LocaleManager $locales, AbstractSerializer $userSerializer, Application $app)
    {
        $this->layout = $layout;
        $this->api = $api;
        $this->assets = $assets;
        $this->view = $view;
        $this->locales = $locales;
        $this->userSerializer = $userSerializer;
        $this->app = $app;

        $this->addHeadString('<link rel="stylesheet" href="//fonts.googleapis.com/css?family=Open+Sans:400italic,700italic,400,700,600">', 'font');

        $this->js = $this->assets->getJs();
        $this->css = $this->assets->getCss();

        $locale = $this->locales->getLocale();
        $this->localeJs = $this->assets->getLocaleJs($locale);
        $this->localeCss = $this->assets->getLocaleCss($locale);

        foreach ($this->locales->getJsFiles($locale) as $file) {
            $this->localeJs->addFile($file);
        }

        foreach ($this->locales->getCssFiles($locale) as $file) {
            $this->localeCss->addFile($file);
        }
    }

    /**
     * Add a string to be appended to the page's <head>.
     *
     * @param string $string
     * @param null|string $name
     */
    public function addHeadString($string, $name = null)
    {
        if ($name) {
            $this->head[$name] = $string;
        } else {
            $this->head[] = $string;
        }
    }

    /**
     * Add a string to be prepended before the page's </body>.
     *
     * @param string $string
     */
    public function addFootString($string)
    {
        $this->foot[] = $string;
    }

    /**
     * Configure a <link> tag.
     *
     * @param string $relation
     * @param string $target
     */
    public function link($relation, $target)
    {
        $this->links[$relation] = $target;
    }

    /**
     * Configure the canonical URL for this page.
     *
     * This will signal to search engines what URL should be used for this
     * content, if it can be found under multiple addresses. This is an
     * important tool to tackle duplicate content.
     *
     * @param string $url
     */
    public function setCanonicalUrl($url)
    {
        $this->link('canonical', $url);
    }

    /**
     * Set a variable to be preloaded into the app.
     *
     * @param string $name
     * @param mixed $value
     */
    public function setVariable($name, $value)
    {
        $this->variables[$name] = $value;
    }

    /**
     * Add a JavaScript module to be imported before the app is booted.
     *
     * @param string $module
     */
    public function loadModule($module)
    {
        $this->modules[] = $module;
    }

    /**
     * Get the string contents of the view.
     *
     * @param Request $request
     * @return string
     */
    public function render(Request $request)
    {
        $forum = $this->getForumDocument($request);

        $this->view->share('translator', $this->locales->getTranslator());
        $this->view->share('allowJs', ! array_get($request->getQueryParams(), 'nojs'));
        $this->view->share('forum', array_get($forum, 'data'));
        $this->view->share('debug', $this->app->inDebugMode());

        $view = $this->view->file(__DIR__.'/../../../views/app.blade.php');

        $view->title = $this->buildTitle(array_get($forum, 'data.attributes.title'));
        $view->description = $this->description ?: array_get($forum, 'data.attributes.description');
        $view->language = $this->language ?: $this->locales->getLocale();
        $view->direction = $this->direction ?: 'ltr';

        $view->modules = $this->modules;
        $view->payload = $this->buildPayload($request, $forum);

        $view->layout = $this->buildLayout();

        $baseUrl = array_get($forum, 'data.attributes.baseUrl');
        $view->cssUrls = $this->buildCssUrls($baseUrl);
        $view->jsUrls = $this->buildJsUrls($baseUrl);

        $view->head = $this->buildHeadContent();
        $view->foot = implode("\n", $this->foot);

        return $view->render();
    }

    protected function buildTitle($forumTitle)
    {
        return ($this->title ? $this->title.' - ' : '').$forumTitle;
    }

    protected function buildPayload(Request $request, $forum)
    {
        $data = $this->getDataFromDocument($forum);

        if ($request->getAttribute('actor')->exists) {
            $user = $this->getUserDocument($request);
            $data = array_merge($data, $this->getDataFromDocument($user));
        }

        $payload = [
            'resources' => $data,
            'session' => $this->buildSession($request),
            'document' => $this->document,
            'locales' => $this->locales->getLocales(),
            'locale' => $this->locales->getLocale()
        ];

        return array_merge($payload, $this->variables);
    }

    protected function buildLayout()
    {
        $view = $this->view->file($this->layout);

        $view->content = $this->buildContent();

        return $view;
    }

    protected function buildContent()
    {
        $view = $this->view->file(__DIR__.'/../../../views/content.blade.php');

        $view->content = $this->content;

        return $view;
    }

    protected function buildCssUrls($baseUrl)
    {
        return $this->buildAssetUrls($baseUrl, [$this->css->getFile(), $this->localeCss->getFile()]);
    }

    protected function buildJsUrls($baseUrl)
    {
        return $this->buildAssetUrls($baseUrl, [$this->js->getFile(), $this->localeJs->getFile()]);
    }

    protected function buildAssetUrls($baseUrl, $files)
    {
        return array_map(function ($file) use ($baseUrl) {
            return $baseUrl.str_replace(public_path(), '', $file);
        }, array_filter($files));
    }

    protected function buildHeadContent()
    {
        $html = implode("\n", $this->head);

        foreach ($this->links as $rel => $href) {
            $html .= "\n<link rel=\"$rel\" href=\"$href\" />";
        }

        return $html;
    }

    /**
     * @return CompilerInterface
     */
    public function getJs()
    {
        return $this->js;
    }

    /**
     * @return CompilerInterface
     */
    public function getCss()
    {
        return $this->css;
    }

    /**
     * @return JsCompiler
     */
    public function getLocaleJs()
    {
        return $this->localeJs;
    }

    /**
     * @return CompilerInterface
     */
    public function getLocaleCss()
    {
        return $this->localeCss;
    }

    /**
     * Get the result of an API request to show the forum.
     *
     * @param Request $request
     * @return array
     */
    protected function getForumDocument(Request $request)
    {
        $actor = $request->getAttribute('actor');

        $response = $this->api->send('Flarum\Api\Controller\ShowForumController', $actor);

        return json_decode($response->getBody(), true);
    }

    /**
     * Get the result of an API request to show the current user.
     *
     * @param Request $request
     * @return array
     */
    protected function getUserDocument(Request $request)
    {
        $actor = $request->getAttribute('actor');

        $this->userSerializer->setActor($actor);

        $resource = new Resource($actor, $this->userSerializer);

        $document = new Document($resource->with('groups'));

        return $document->toArray();
    }

    /**
     * Get information about the current session.
     *
     * @param Request $request
     * @return array
     */
    protected function buildSession(Request $request)
    {
        $actor = $request->getAttribute('actor');
        $session = $request->getAttribute('session');

        return [
            'userId' => $actor->id,
            'csrfToken' => $session->get('csrf_token')
        ];
    }

    /**
     * Get an array of data by merging the 'data' and 'included' keys of a
     * JSON-API document.
     *
     * @param array $document
     * @return array
     */
    private function getDataFromDocument(array $document)
    {
        $data[] = $document['data'];

        if (isset($document['included'])) {
            $data = array_merge($data, $document['included']);
        }

        return $data;
    }
}
