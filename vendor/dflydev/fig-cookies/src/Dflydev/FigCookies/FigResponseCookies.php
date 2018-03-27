<?php

namespace Dflydev\FigCookies;

use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;

class FigResponseCookies
{
    /**
     * @param ResponseInterface $response
     * @param string $name
     * @param string|null $value
     *
     * @return SetCookie
     */
    public static function get(ResponseInterface $response, $name, $value = null)
    {
        $setCookies = SetCookies::fromResponse($response);
        if ($setCookies->has($name)) {
            return $setCookies->get($name);
        }

        return SetCookie::create($name, $value);
    }

    /**
     * @param ResponseInterface $response
     * @param SetCookie $setCookie
     *
     * @return ResponseInterface
     */
    public static function set(ResponseInterface $response, SetCookie $setCookie)
    {
        return SetCookies::fromResponse($response)
            ->with($setCookie)
            ->renderIntoSetCookieHeader($response)
        ;
    }

    /**
     * @param ResponseInterface $response
     * @param string $cookieName
     *
     * @return ResponseInterface
     */
    public static function expire(ResponseInterface $response, $cookieName)
    {
        return static::set($response, SetCookie::createExpired($cookieName));
    }

    /**
     * @param ResponseInterface $response
     * @param string $name
     * @param callable $modify
     *
     * @return ResponseInterface
     */
    public static function modify(ResponseInterface $response, $name, $modify)
    {
        if (! is_callable($modify)) {
            throw new InvalidArgumentException('$modify must be callable.');
        }

        $setCookies = SetCookies::fromResponse($response);
        $setCookie = $modify($setCookies->has($name)
            ? $setCookies->get($name)
            : SetCookie::create($name)
        );

        return $setCookies
            ->with($setCookie)
            ->renderIntoSetCookieHeader($response)
        ;
    }

    /**
     * @param ResponseInterface $response
     * @param string $name
     *
     * @return ResponseInterface
     */
    public static function remove(ResponseInterface $response, $name)
    {
        return SetCookies::fromResponse($response)
            ->without($name)
            ->renderIntoSetCookieHeader($response)
        ;
    }
}
