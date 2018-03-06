<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Api\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Zend\Stratigility\MiddlewareInterface;

class FakeHttpMethods implements MiddlewareInterface
{
    const HEADER_NAME = 'x-http-method-override';

    /**
     * {@inheritdoc}
     */
    public function __invoke(Request $request, Response $response, callable $out = null)
    {
        if ($request->getMethod() === 'POST' && $request->hasHeader(self::HEADER_NAME)) {
            $fakeMethod = $request->getHeaderLine(self::HEADER_NAME);

            $request = $request->withMethod(strtoupper($fakeMethod));
        }

        return $out ? $out($request, $response) : $response;
    }
}
