<?php
/**
 * @link      http://github.com/zendframework/zend-stratigility for the canonical source repository
 * @copyright Copyright (c) 2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\Stratigility\Middleware;

use PHPUnit_Framework_TestCase as TestCase;
use Prophecy\Argument;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Zend\Stratigility\Middleware\OriginalMessages;

class OriginalMessagesTest extends TestCase
{
    public function setUp()
    {
        $this->uri = $this->prophesize(UriInterface::class);
        $this->request = $this->prophesize(ServerRequestInterface::class);
        $this->response = $this->prophesize(ResponseInterface::class);
    }

    public function testNotPassingNextArgumentReturnsResponseVerbatim()
    {
        $middleware = new OriginalMessages();

        $this->request->getUri()->shouldNotBeCalled();
        $response = $middleware(
            $this->request->reveal(),
            $this->response->reveal()
        );

        $this->assertSame($this->response->reveal(), $response);
    }

    public function testNextReceivesRequestWithNewAttributes()
    {
        $middleware = new OriginalMessages();
        $expected   = $this->prophesize(ResponseInterface::class)->reveal();

        $next = function ($request, $response) use ($expected) {
            return $expected;
        };

        $this->request->getUri()->will([$this->uri, 'reveal']);
        $this->request->withAttribute(
            'originalUri',
            Argument::that(function ($arg) {
                $this->assertSame($this->uri->reveal(), $arg);
                return $arg;
            })
        )->will([$this->request, 'reveal']);

        $this->request->withAttribute(
            'originalRequest',
            Argument::that(function ($arg) {
                $this->assertSame($this->request->reveal(), $arg);
                return $arg;
            })
        )->will([$this->request, 'reveal']);

        $this->request->withAttribute(
            'originalResponse',
            Argument::that(function ($arg) {
                $this->assertSame($this->response->reveal(), $arg);
                return $arg;
            })
        )->will([$this->request, 'reveal']);

        $response = $middleware(
            $this->request->reveal(),
            $this->response->reveal(),
            $next
        );

        $this->assertSame($expected, $response);
    }
}
