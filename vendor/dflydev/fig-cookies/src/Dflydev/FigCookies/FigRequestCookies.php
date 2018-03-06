<?php

namespace Dflydev\FigCookies;

use InvalidArgumentException;
use Psr\Http\Message\RequestInterface;

class FigRequestCookies
{
    /**
     * @param RequestInterface $request
     * @param string $name
     * @param string|null $value
     *
     * @return Cookie
     */
    public static function get(RequestInterface $request, $name, $value = null)
    {
        $cookies = Cookies::fromRequest($request);
        if ($cookies->has($name)) {
            return $cookies->get($name);
        }

        return Cookie::create($name, $value);
    }

    /**
     * @param RequestInterface $request
     * @param Cookie $cookie
     *
     * @return RequestInterface
     */
    public static function set(RequestInterface $request, Cookie $cookie)
    {
        return Cookies::fromRequest($request)
            ->with($cookie)
            ->renderIntoCookieHeader($request)
        ;
    }

    /**
     * @param RequestInterface $request
     * @param string $name
     * @param callable $modify
     *
     * @return RequestInterface
     */
    public static function modify(RequestInterface $request, $name, $modify)
    {
        if (! is_callable($modify)) {
            throw new InvalidArgumentException('$modify must be callable.');
        }

        $cookies = Cookies::fromRequest($request);
        $cookie = $modify($cookies->has($name)
            ? $cookies->get($name)
            : Cookie::create($name)
        );

        return $cookies
            ->with($cookie)
            ->renderIntoCookieHeader($request)
        ;
    }

    /**
     * @param RequestInterface $request
     * @param string $name
     *
     * @return RequestInterface
     */
    public static function remove(RequestInterface $request, $name)
    {
        return Cookies::fromRequest($request)
            ->without($name)
            ->renderIntoCookieHeader($request)
        ;
    }
}
