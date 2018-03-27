<?php

namespace Dflydev\FigCookies;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

class FigCookieTestingRequest implements RequestInterface
{
    use FigCookieTestingMessage;

    public function getRequestTarget()
    {
        throw new \RuntimeException("This method has not been implemented.");
    }

    public function withRequestTarget($requestTarget)
    {
        throw new \RuntimeException("This method has not been implemented.");
    }

    public function getMethod()
    {
        throw new \RuntimeException("This method has not been implemented.");
    }

    public function withMethod($method)
    {
        throw new \RuntimeException("This method has not been implemented.");
    }

    public function getUri()
    {
        throw new \RuntimeException("This method has not been implemented.");
    }

    public function withUri(UriInterface $uri, $preserveHost = false)
    {
        throw new \RuntimeException("This method has not been implemented.");
    }
}
