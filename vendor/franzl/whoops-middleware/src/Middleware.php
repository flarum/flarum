<?php

namespace Franzl\Middleware\Whoops;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

/**
 * Middleware class for "typical" PSR-7 middleware
 */
class Middleware
{
    public function __invoke(Request $request, Response $response, callable $next = null)
    {
        try {
            return $next($request, $response);
        } catch (\Exception $e) {
            return WhoopsRunner::handle($e, $request);
        }
    }
}
