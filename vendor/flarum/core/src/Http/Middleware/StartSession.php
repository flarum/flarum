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

use Dflydev\FigCookies\FigResponseCookies;
use Flarum\Http\CookieFactory;
use Illuminate\Support\Str;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Zend\Stratigility\MiddlewareInterface;

class StartSession implements MiddlewareInterface
{
    /**
     * @var CookieFactory
     */
    protected $cookie;

    /**
     * Rememberer constructor.
     * @param CookieFactory $cookie
     */
    public function __construct(CookieFactory $cookie)
    {
        $this->cookie = $cookie;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke(Request $request, Response $response, callable $out = null)
    {
        $session = $this->startSession();

        $request = $request->withAttribute('session', $session);

        $response = $out ? $out($request, $response) : $response;

        $response = $this->withCsrfTokenHeader($response, $session);

        return $this->withSessionCookie($response, $session);
    }

    private function startSession()
    {
        $session = new Session;

        $session->setName('flarum_session');
        $session->start();

        if (! $session->has('csrf_token')) {
            $session->set('csrf_token', Str::random(40));
        }

        return $session;
    }

    private function withCsrfTokenHeader(Response $response, SessionInterface $session)
    {
        if ($session->has('csrf_token')) {
            $response = $response->withHeader('X-CSRF-Token', $session->get('csrf_token'));
        }

        return $response;
    }

    private function withSessionCookie(Response $response, SessionInterface $session)
    {
        return FigResponseCookies::set(
            $response,
            $this->cookie->make($session->getName(), $session->getId())
        );
    }
}
