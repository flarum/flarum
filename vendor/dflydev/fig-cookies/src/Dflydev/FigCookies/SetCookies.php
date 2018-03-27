<?php

namespace Dflydev\FigCookies;

use Psr\Http\Message\ResponseInterface;

class SetCookies
{
    /**
     * The name of the Set-Cookie header.
     */
    const SET_COOKIE_HEADER = 'Set-Cookie';

    /**
     * @var SetCookie[]
     */
    private $setCookies = [];

    /**
     * @param SetCookie[] $setCookies
     */
    public function __construct(array $setCookies = [])
    {
        foreach ($setCookies as $setCookie) {
            $this->setCookies[$setCookie->getName()] = $setCookie;
        }
    }

    /**
     * @param string $name
     * @return bool
     */
    public function has($name)
    {
        return isset($this->setCookies[$name]);
    }

    /**
     * @param string $name
     * @return SetCookie|null
     */
    public function get($name)
    {
        if (! $this->has($name)) {
            return null;
        }

        return $this->setCookies[$name];
    }

    /**
     * @return SetCookie[]
     */
    public function getAll()
    {
        return array_values($this->setCookies);
    }

    /**
     * @param SetCookie $setCookie
     * @return SetCookies
     */
    public function with(SetCookie $setCookie)
    {
        $clone = clone($this);

        $clone->setCookies[$setCookie->getName()] = $setCookie;

        return $clone;
    }

    /**
     * @param string $name
     * @return SetCookies
     */
    public function without($name)
    {
        $clone = clone($this);

        if (! $clone->has($name)) {
            return $clone;
        }

        unset($clone->setCookies[$name]);

        return $clone;
    }

    /**
     * Render SetCookies into a Response.
     *
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function renderIntoSetCookieHeader(ResponseInterface $response)
    {
        $response = $response->withoutHeader(static::SET_COOKIE_HEADER);
        foreach ($this->setCookies as $setCookie) {
            $response = $response->withAddedHeader(static::SET_COOKIE_HEADER, (string) $setCookie);
        }

        return $response;
    }

    /**
     * Create SetCookies from a collection of SetCookie header value strings.
     *
     * @param string[] $setCookieStrings
     * @return static
     */
    public static function fromSetCookieStrings($setCookieStrings)
    {
        return new static(array_map(function ($setCookieString) {
            return SetCookie::fromSetCookieString($setCookieString);
        }, $setCookieStrings));
    }

    /**
     * Create SetCookies from a Response.
     *
     * @param ResponseInterface $response
     * @return SetCookies
     */
    public static function fromResponse(ResponseInterface $response)
    {
        return new static(array_map(function ($setCookieString) {
            return SetCookie::fromSetCookieString($setCookieString);
        }, $response->getHeader(static::SET_COOKIE_HEADER)));
    }
}
