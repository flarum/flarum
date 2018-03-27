<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @see       http://github.com/zendframework/zend-stratigility for the canonical source repository
 * @copyright Copyright (c) 2015-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-stratigility/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Stratigility;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

/**
 * Middleware for handling errors.
 *
 * Error middleware is essentially the same as the `MiddlewareInterface`, with
 * one key distinction: it has an additional argument prepended, representing
 * an error condition.
 *
 * `Next` will skip error middleware if called without an error; conversely,
 * if called with an error, it will skip normal middleware.
 *
 * Error middleware does something with the arguments passed, and then
 * either returns a response, or calls `$out`, with or without the error.
 *
 * @deprecated since 1.3.0; will be removed with 2.0.0. Please see
 *     https://docs.zendframework.com/zend-stratigility/migration/to-v2/
 *     for more information on how to update your code for forwards
 *     compatibility.
 */
interface ErrorMiddlewareInterface
{
    /**
     * Process an incoming error, along with associated request and response.
     *
     * Accepts an error, a server-side request, and a response instance, and
     * does something with them; if further processing can be done, it can
     * delegate to `$out`.
     *
     * @see MiddlewareInterface
     * @param mixed $error
     * @param Request $request
     * @param Response $response
     * @param null|callable $out
     * @return null|Response
     */
    public function __invoke($error, Request $request, Response $response, callable $out = null);
}
