<?php

namespace Dflydev\FigCookies;

use Psr\Http\Message\ResponseInterface;

class FigCookieTestingResponse implements ResponseInterface
{
    use FigCookieTestingMessage;

    public function getStatusCode()
    {
        throw new \RuntimeException("This method has not been implemented.");
    }

    public function withStatus($code, $reasonPhrase = '')
    {
        throw new \RuntimeException("This method has not been implemented.");
    }

    public function getReasonPhrase()
    {
        throw new \RuntimeException("This method has not been implemented.");
    }

}
