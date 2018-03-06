<?php
/**
 * @link      http://github.com/zendframework/zend-stratigility for the canonical source repository
 * @copyright Copyright (c) 2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\Stratigility\Middleware;

use Closure;
use Interop\Http\Middleware\DelegateInterface;
use PHPUnit_Framework_TestCase as TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Stratigility\Middleware\CallableMiddlewareWrapper;
use Zend\Stratigility\Next;

class CallableMiddlewareWrapperTest extends TestCase
{
    public function testWrapperDecoratesAndProxiesToCallableMiddleware()
    {
        $request = $this->prophesize(ServerRequestInterface::class)->reveal();
        $delegate = $this->prophesize(DelegateInterface::class)->reveal();
        $response = $this->prophesize(ResponseInterface::class)->reveal();

        $decorator = new CallableMiddlewareWrapper(
            function ($request, $response, $delegate) {
                return $response;
            },
            $response
        );

        $this->assertSame($response, $decorator->process($request, $delegate));
    }

    public function testWrapperDoesNotDecorateNextInstancesWhenProxying()
    {
        $request = $this->prophesize(ServerRequestInterface::class)->reveal();
        $response = $this->prophesize(ResponseInterface::class)->reveal();

        $delegate = $this->prophesize(Next::class)->reveal();
        $decorator = new CallableMiddlewareWrapper(
            function ($request, $response, $next) use ($delegate) {
                $this->assertSame($delegate, $next);
                return $response;
            },
            $response
        );

        $this->assertSame($response, $decorator->process($request, $delegate));
    }

    public function testWrapperDecoratesDelegatesNotExtendingNext()
    {
        $request = $this->prophesize(ServerRequestInterface::class)->reveal();
        $response = $this->prophesize(ResponseInterface::class)->reveal();

        $delegate = $this->prophesize(DelegateInterface::class)->reveal();
        $decorator = new CallableMiddlewareWrapper(
            function ($request, $response, $next) use ($delegate) {
                $this->assertNotSame($delegate, $next);
                $this->assertInstanceOf(Closure::class, $next);
                return $response;
            },
            $response
        );

        $this->assertSame($response, $decorator->process($request, $delegate));
    }

    public function testDecoratedDelegateWillBeInvokedWithOnlyRequest()
    {
        $request = $this->prophesize(ServerRequestInterface::class)->reveal();
        $response = $this->prophesize(ResponseInterface::class)->reveal();
        $expected = $this->prophesize(ResponseInterface::class)->reveal();

        $delegate = $this->prophesize(DelegateInterface::class);
        $delegate->process($request)->willReturn($expected);

        $decorator = new CallableMiddlewareWrapper(
            function ($request, $response, $next) {
                return $next($request, $response);
            },
            $response
        );

        $this->assertSame($expected, $decorator->process($request, $delegate->reveal()));
    }
}
