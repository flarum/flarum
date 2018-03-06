<?php

namespace Dflydev\FigCookies;

use Psr\Http\Message\RequestInterface;

class Cookies
{
    /**
     * The name of the Cookie header.
     */
    const COOKIE_HEADER = 'Cookie';

    /**
     * @var Cookie[]
     */
    private $cookies = [];

    /**
     * @param Cookie[] $cookies
     */
    public function __construct(array $cookies = [])
    {
        foreach ($cookies as $cookie) {
            $this->cookies[$cookie->getName()] = $cookie;
        }
    }

    /**
     * @param $name
     * @return bool
     */
    public function has($name)
    {
        return isset($this->cookies[$name]);
    }

    /**
     * @param $name
     * @return Cookie|null
     */
    public function get($name)
    {
        if (! $this->has($name)) {
            return null;
        }

        return $this->cookies[$name];
    }

    /**
     * @return Cookie[]
     */
    public function getAll()
    {
        return array_values($this->cookies);
    }

    /**
     * @param Cookie $cookie
     * @return Cookies
     */
    public function with(Cookie $cookie)
    {
        $clone = clone($this);

        $clone->cookies[$cookie->getName()] = $cookie;

        return $clone;
    }

    /**
     * @param $name
     * @return Cookies
     */
    public function without($name)
    {
        $clone = clone($this);

        if (! $clone->has($name)) {
            return $clone;
        }

        unset($clone->cookies[$name]);

        return $clone;
    }

    /**
     * Render Cookies into a Request.
     *
     * @param RequestInterface $request
     * @return RequestInterface
     */
    public function renderIntoCookieHeader(RequestInterface $request)
    {
        $cookieString = implode('; ', $this->cookies);

        $request = $request->withHeader(static::COOKIE_HEADER, $cookieString);

        return $request;
    }

    /**
     * Create Cookies from a Cookie header value string.
     *
     * @param $string
     * @return static
     */
    public static function fromCookieString($string)
    {
        return new static(Cookie::listFromCookieString($string));
    }

    /**
     * Create Cookies from a Request.
     *
     * @param RequestInterface $request
     * @return Cookies
     */
    public static function fromRequest(RequestInterface $request)
    {
        $cookieString = $request->getHeaderLine(static::COOKIE_HEADER);

        return static::fromCookieString($cookieString);
    }
}
