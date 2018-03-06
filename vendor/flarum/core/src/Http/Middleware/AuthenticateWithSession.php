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

use Flarum\Core\Guest;
use Flarum\Core\User;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Zend\Stratigility\MiddlewareInterface;

class AuthenticateWithSession implements MiddlewareInterface
{
    /**
     * {@inheritdoc}
     */
    public function __invoke(Request $request, Response $response, callable $out = null)
    {
        $session = $request->getAttribute('session');

        $actor = $this->getActor($session);

        $actor->setSession($session);

        $request = $request->withAttribute('actor', $actor);

        return $out ? $out($request, $response) : $response;
    }

    private function getActor(SessionInterface $session)
    {
        $actor = User::find($session->get('user_id')) ?: new Guest;

        if ($actor->exists) {
            $actor->updateLastSeen()->save();
        }

        return $actor;
    }
}
