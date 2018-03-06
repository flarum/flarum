<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Update\Controller;

use Flarum\Http\Controller\AbstractHtmlController;
use Illuminate\Contracts\View\Factory;
use Psr\Http\Message\ServerRequestInterface as Request;

class IndexController extends AbstractHtmlController
{
    /**
     * @var Factory
     */
    protected $view;

    /**
     * @param Factory $view
     */
    public function __construct(Factory $view)
    {
        $this->view = $view;
    }

    /**
     * @param Request $request
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function render(Request $request)
    {
        $view = $this->view->make('flarum.update::app')->with('title', 'Update Flarum');

        $view->with('content', $this->view->make('flarum.update::update'));

        return $view;
    }
}
