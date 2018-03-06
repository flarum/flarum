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

use Exception;
use Flarum\Api\ErrorHandler;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class HandleErrors
{
    /**
     * @var ErrorHandler
     */
    protected $errorHandler;

    /**
     * @param ErrorHandler $errorHandler
     */
    public function __construct(ErrorHandler $errorHandler)
    {
        $this->errorHandler = $errorHandler;
    }

    /**
     * Catch all errors that happen during further middleware execution.
     *
     * @param Request $request
     * @param Response $response
     * @param callable $out
     * @return Response
     */
    public function __invoke(Request $request, Response $response, callable $out = null)
    {
        try {
            return $out($request, $response);
        } catch (Exception $e) {
            return $this->errorHandler->handle($e);
        }
    }
}
