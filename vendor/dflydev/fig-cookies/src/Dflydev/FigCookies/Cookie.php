<?php

namespace Dflydev\FigCookies;

class Cookie
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string|null
     */
    private $value;

    /**
     * @param string $name
     * @param string|null $value
     */
    public function __construct($name, $value = null)
    {
        $this->name = $name;
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string|null
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param string|null $value
     * @return Cookie
     */
    public function withValue($value = null)
    {
        $clone = clone($this);

        $clone->value = $value;

        return $clone;
    }

    /**
     * Render Cookie as a string.
     *
     * @return string
     */
    public function __toString()
    {
        return urlencode($this->name).'='.urlencode($this->value);
    }

    /**
     * Create a Cookie.
     *
     * @param string $name
     * @param string|null $value
     * @return Cookie
     */
    public static function create($name, $value = null)
    {
        return new static($name, $value);
    }

    /**
     * Create a list of Cookies from a Cookie header value string.
     *
     * @param string $string
     * @return Cookie[]
     */
    public static function listFromCookieString($string)
    {
        $cookies = StringUtil::splitOnAttributeDelimiter($string);

        return array_map(function ($cookiePair) {
            return static::oneFromCookiePair($cookiePair);
        }, $cookies);
    }

    /**
     * Create one Cookie from a cookie key/value header value string.
     *
     * @param string $string
     * @return Cookie
     */
    public static function oneFromCookiePair($string)
    {
        list ($cookieName, $cookieValue) = StringUtil::splitCookiePair($string);

        /** @var Cookie $cookie */
        $cookie = new static($cookieName);

        if (! is_null($cookieValue)) {
            $cookie = $cookie->withValue($cookieValue);
        }

        return $cookie;
    }
}
