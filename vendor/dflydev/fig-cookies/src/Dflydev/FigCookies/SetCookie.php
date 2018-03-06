<?php

namespace Dflydev\FigCookies;

use DateTime;
use DateTimeInterface;

class SetCookie
{
    private $name;
    private $value;
    private $expires = 0;
    private $maxAge = 0;
    private $path;
    private $domain;
    private $secure = false;
    private $httpOnly = false;

    private function __construct($name, $value = null)
    {
        $this->name = $name;
        $this->value = $value;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function getExpires()
    {
        return $this->expires;
    }

    public function getMaxAge()
    {
        return $this->maxAge;
    }

    public function getPath()
    {
        return $this->path;
    }

    public function getDomain()
    {
        return $this->domain;
    }

    public function getSecure()
    {
        return $this->secure;
    }

    public function getHttpOnly()
    {
        return $this->httpOnly;
    }

    public function withValue($value = null)
    {
        $clone = clone($this);

        $clone->value = $value;

        return $clone;
    }

    private function resolveExpires($expires = null)
    {
        if (is_null($expires)) {
            return null;
        }

        if ($expires instanceof DateTime || $expires instanceof DateTimeInterface) {
            return $expires->getTimestamp();
        }

        if (is_numeric($expires)) {
            return $expires;
        }

        return strtotime($expires);
    }

    public function withExpires($expires = null)
    {
        $expires = $this->resolveExpires($expires);

        $clone = clone($this);

        $clone->expires = $expires;

        return $clone;
    }

    public function rememberForever()
    {
        return $this->withExpires(new DateTime('+5 years'));
    }

    public function expire()
    {
        return $this->withExpires(new DateTime('-5 years'));
    }

    public function withMaxAge($maxAge = null)
    {
        $clone = clone($this);

        $clone->maxAge = $maxAge;

        return $clone;
    }

    public function withPath($path = null)
    {
        $clone = clone($this);

        $clone->path = $path;

        return $clone;
    }

    public function withDomain($domain = null)
    {
        $clone = clone($this);

        $clone->domain = $domain;

        return $clone;
    }

    public function withSecure($secure = null)
    {
        $clone = clone($this);

        $clone->secure = $secure;

        return $clone;
    }

    public function withHttpOnly($httpOnly = null)
    {
        $clone = clone($this);

        $clone->httpOnly = $httpOnly;

        return $clone;
    }

    public function __toString()
    {
        $cookieStringParts = [
            urlencode($this->name).'='.urlencode($this->value),
        ];

        $cookieStringParts = $this->appendFormattedDomainPartIfSet($cookieStringParts);
        $cookieStringParts = $this->appendFormattedPathPartIfSet($cookieStringParts);
        $cookieStringParts = $this->appendFormattedExpiresPartIfSet($cookieStringParts);
        $cookieStringParts = $this->appendFormattedMaxAgePartIfSet($cookieStringParts);
        $cookieStringParts = $this->appendFormattedSecurePartIfSet($cookieStringParts);
        $cookieStringParts = $this->appendFormattedHttpOnlyPartIfSet($cookieStringParts);

        return implode('; ', $cookieStringParts);
    }

    public static function create($name, $value = null)
    {
        return new static($name, $value);
    }

    public static function createRememberedForever($name, $value = null)
    {
        return static::create($name, $value)->rememberForever();
    }

    public static function createExpired($name)
    {
        return static::create($name)->expire();
    }

    public static function fromSetCookieString($string)
    {
        $rawAttributes = StringUtil::splitOnAttributeDelimiter($string);

        list ($cookieName, $cookieValue) = StringUtil::splitCookiePair(array_shift($rawAttributes));

        /** @var SetCookie $setCookie */
        $setCookie = new static($cookieName);

        if (! is_null($cookieValue)) {
            $setCookie = $setCookie->withValue($cookieValue);
        }

        while ($rawAttribute = array_shift($rawAttributes)) {
            $rawAttributePair = explode('=', $rawAttribute, 2);

            $attributeKey = $rawAttributePair[0];
            $attributeValue = count($rawAttributePair) > 1 ? $rawAttributePair[1] : null;

            $attributeKey = strtolower($attributeKey);

            switch ($attributeKey) {
                case 'expires':
                    $setCookie = $setCookie->withExpires($attributeValue);
                    break;
                case 'max-age':
                    $setCookie = $setCookie->withMaxAge($attributeValue);
                    break;
                case 'domain':
                    $setCookie = $setCookie->withDomain($attributeValue);
                    break;
                case 'path':
                    $setCookie = $setCookie->withPath($attributeValue);
                    break;
                case 'secure':
                    $setCookie = $setCookie->withSecure(true);
                    break;
                case 'httponly':
                    $setCookie = $setCookie->withHttpOnly(true);
                    break;
            }

        }

        return $setCookie;
    }
    private function appendFormattedDomainPartIfSet(array $cookieStringParts)
    {
        if ($this->domain) {
            $cookieStringParts[] = sprintf("Domain=%s", $this->domain);
        }

        return $cookieStringParts;
    }

    private function appendFormattedPathPartIfSet(array $cookieStringParts)
    {
        if ($this->path) {
            $cookieStringParts[] = sprintf("Path=%s", $this->path);
        }

        return $cookieStringParts;
    }

    private function appendFormattedExpiresPartIfSet(array $cookieStringParts)
    {
        if ($this->expires) {
            $cookieStringParts[] = sprintf("Expires=%s", gmdate('D, d M Y H:i:s T', $this->expires));
        }

        return $cookieStringParts;
    }

    private function appendFormattedMaxAgePartIfSet(array $cookieStringParts)
    {
        if ($this->maxAge) {
            $cookieStringParts[] = sprintf("Max-Age=%s", $this->maxAge);
        }

        return $cookieStringParts;
    }

    private function appendFormattedSecurePartIfSet(array $cookieStringParts)
    {
        if ($this->secure) {
            $cookieStringParts[] = 'Secure';
        }

        return $cookieStringParts;
    }

    private function appendFormattedHttpOnlyPartIfSet(array $cookieStringParts)
    {
        if ($this->httpOnly) {
            $cookieStringParts[] = 'HttpOnly';
        }

        return $cookieStringParts;
    }
}
