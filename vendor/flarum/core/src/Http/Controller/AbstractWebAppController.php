<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Http\Controller;

use Flarum\Event\ConfigureClientView;
use Flarum\Event\ConfigureWebApp;
use Flarum\Http\WebApp\AbstractWebApp;
use Illuminate\Contracts\Events\Dispatcher;
use Psr\Http\Message\ServerRequestInterface as Request;

abstract class AbstractWebAppController extends AbstractHtmlController
{
    /**
     * @var AbstractWebApp
     */
    protected $webApp;

    /**
     * @var Dispatcher
     */
    protected $events;

    /**
     * {@inheritdoc}
     */
    public function render(Request $request)
    {
        $view = $this->getView($request);

        $this->events->fire(
            new ConfigureClientView($this, $view, $request)
        );
        $this->events->fire(
            new ConfigureWebApp($this, $view, $request)
        );

        return $view->render($request);
    }

    /**
     * @param Request $request
     * @return \Flarum\Http\WebApp\WebAppView
     */
    protected function getView(Request $request)
    {
        return $this->webApp->getView();
    }
}
