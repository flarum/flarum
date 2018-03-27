<?php

namespace Franzl\Middleware\Whoops;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

/**
 * ErrorMiddleware class for use with Zend's Stratigility middleware pipe
 */
class ErrorMiddleware
{
    public function __invoke($error, Request $request, Response $response, callable $out)
    {
        return WhoopsRunner::handle($error, $request);
    }
}
