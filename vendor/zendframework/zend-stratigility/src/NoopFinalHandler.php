<?php
/**
 * @link      http://github.com/zendframework/zend-stratigility for the canonical source repository
 * @copyright Copyright (c) 2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Stratigility;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class NoopFinalHandler
{
    /**
     * Final handler for all requests.
     *
     * This handler should only ever be invoked if Next exhausts its stack.
     *
     * When that happens, it returns the response provided during invocation.
     *
     * @param ServerRequestInterface $request Request instance.
     * @param ResponseInterface $response Response instance.
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        return $response;
    }
}
