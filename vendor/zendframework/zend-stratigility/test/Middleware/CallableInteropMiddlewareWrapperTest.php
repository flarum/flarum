<?php
/**
 * @link      http://github.com/zendframework/zend-stratigility for the canonical source repository
 * @copyright Copyright (c) 2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\Stratigility\Middleware;

use Interop\Http\Middleware\DelegateInterface;
use PHPUnit_Framework_TestCase as TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Stratigility\Middleware\CallableInteropMiddlewareWrapper;

class CallableInteropMiddlewareWrapperTest extends TestCase
{
    public function testWrapperDecoratesAndProxiesToCallableInteropMiddleware()
    {
        $request = $this->prophesize(ServerRequestInterface::class)->reveal();
        $delegate = $this->prophesize(DelegateInterface::class)->reveal();
        $response = $this->prophesize(ResponseInterface::class)->reveal();

        $decorator = new CallableInteropMiddlewareWrapper(
            function ($request, DelegateInterface $delegate) use ($response) {
                return $response;
            }
        );

        $this->assertSame($response, $decorator->process($request, $delegate));
    }
}
