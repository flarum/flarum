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

use Dflydev\FigCookies\SetCookie;
use Flarum\Foundation\Application;

class CookieFactory
{
    /**
     * @var Application
     */
    protected $app;

    /**
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Make a new cookie instance.
     *
     * This method returns a cookie instance for use with the Set-Cookie HTTP header.
     * It will be pre-configured according to Flarum's base URL and protocol.
     *
     * @param  string  $name
     * @param  string  $value
     * @param  int     $maxAge
     * @return \Dflydev\FigCookies\SetCookie
     */
    public function make($name, $value = null, $maxAge = null)
    {
        // Parse the forum's base URL so that we can determine the optimal cookie settings
        $url = parse_url(rtrim($this->app->url(), '/'));

        $cookie = SetCookie::create($name, $value);

        // Make sure we send both the MaxAge and Expires parameters (the former
        // is not supported by all browser versions)
        if ($maxAge) {
            $cookie = $cookie
                ->withMaxAge($maxAge)
                ->withExpires(time() + $maxAge);
        }

        return $cookie
            ->withPath(array_get($url, 'path') ?: '/')
            ->withSecure(array_get($url, 'scheme') === 'https')
            ->withHttpOnly(true);
    }
}
