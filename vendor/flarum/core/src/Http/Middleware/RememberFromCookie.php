<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Http\Middleware;

use Flarum\Http\AccessToken;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Zend\Stratigility\MiddlewareInterface;

class RememberFromCookie implements MiddlewareInterface
{
    /**
     * {@inheritdoc}
     */
    public function __invoke(Request $request, Response $response, callable $out = null)
    {
        $id = array_get($request->getCookieParams(), 'flarum_remember');

        if ($id) {
            $token = AccessToken::find($id);

            if ($token) {
                $token->touch();

                $session = $request->getAttribute('session');
                $session->set('user_id', $token->user_id);
            }
        }

        return $out ? $out($request, $response) : $response;
    }
}
