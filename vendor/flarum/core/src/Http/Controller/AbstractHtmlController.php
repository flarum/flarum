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

use Psr\Http\Message\ServerRequestInterface as Request;
use Zend\Diactoros\Response;

abstract class AbstractHtmlController implements ControllerInterface
{
    /**
     * @param Request $request
     * @return \Zend\Diactoros\Response
     */
    public function handle(Request $request)
    {
        $view = $this->render($request);

        $response = new Response;
        $response->getBody()->write($view);

        return $response;
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Support\Renderable
     */
    abstract protected function render(Request $request);
}
