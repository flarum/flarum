<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Http;

use Dflydev\FigCookies\FigResponseCookies;
use Psr\Http\Message\ResponseInterface;

class Rememberer
{
    protected $cookieName = 'flarum_remember';

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

    public function remember(ResponseInterface $response, AccessToken $token, $session = false)
    {
        $lifetime = null;

        if (! $session) {
            $token->lifetime = $lifetime = 5 * 365 * 24 * 60 * 60; // 5 years
            $token->save();
        }

        return FigResponseCookies::set(
            $response,
            $this->cookie->make($this->cookieName, $token->id, $lifetime)
        );
    }

    public function rememberUser(ResponseInterface $response, $userId)
    {
        $token = AccessToken::generate($userId);

        return $this->remember($response, $token);
    }

    public function forget(ResponseInterface $response)
    {
        return FigResponseCookies::expire($response, $this->cookieName);
    }
}
