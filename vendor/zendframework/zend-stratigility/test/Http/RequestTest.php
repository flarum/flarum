<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @see       http://github.com/zendframework/zend-stratigility for the canonical source repository
 * @copyright Copyright (c) 2015-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-stratigility/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Stratigility\Http;

use PHPUnit_Framework_TestCase as TestCase;
use Zend\Diactoros\ServerRequest as PsrRequest;
use Zend\Diactoros\Uri;
use Zend\Stratigility\Http\Request;

class RequestTest extends TestCase
{
    public $errorHandler;

    public function setUp()
    {
        $this->restoreErrorHandler();
        $this->errorHandler = function ($errno, $errstr) {
            return (false !== strstr($errstr, Request::class . ' is now deprecated'));
        };
        set_error_handler($this->errorHandler, E_USER_DEPRECATED);

        $psrRequest     = new PsrRequest([], [], 'http://example.com/', 'GET', 'php://memory');
        $this->original = $psrRequest;
        $this->request  = new Request($this->original);
    }

    public function tearDown()
    {
        $this->restoreErrorHandler();
    }

    public function restoreErrorHandler()
    {
        if ($this->errorHandler) {
            restore_error_handler();
            $this->errorHandler = null;
        }
    }

    public function testCallingSetUriSetsUriInRequestAndOriginalRequestInClone()
    {
        $url = 'http://example.com/foo';
        $request = $this->request->withUri(new Uri($url));
        $this->assertNotSame($this->request, $request);
        $this->assertSame($this->original, $request->getOriginalRequest());
        $this->assertSame($url, (string) $request->getUri());
    }

    public function testConstructorSetsOriginalRequestIfNoneProvided()
    {
        $url = 'http://example.com/foo';
        $baseRequest = new PsrRequest([], [], $url, 'GET', 'php://memory');

        $request = new Request($baseRequest);
        $this->assertSame($baseRequest, $request->getOriginalRequest());
    }

    public function testCallingSettersRetainsOriginalRequest()
    {
        $url = 'http://example.com/foo';
        $baseRequest = new PsrRequest([], [], $url, 'GET', 'php://memory');

        $request = new Request($baseRequest);
        $request = $request->withMethod('POST');
        $new     = $request->withAddedHeader('X-Foo', 'Bar');

        $this->assertNotSame($request, $new);
        $this->assertNotSame($baseRequest, $new);
        $this->assertNotSame($baseRequest, $new->getCurrentRequest());
        $this->assertSame($baseRequest, $new->getOriginalRequest());
    }

    public function testCanAccessOriginalRequest()
    {
        $this->assertSame($this->original, $this->request->getOriginalRequest());
    }

    public function testDecoratorProxiesToAllMethods()
    {
        $stream = $this->getMockBuilder('Psr\Http\Message\StreamInterface')->getMock();
        $psrRequest = new PsrRequest([], [], 'http://example.com', 'POST', $stream, [
            'Accept' => 'application/xml',
            'X-URL' => 'http://example.com/foo',
        ]);
        $request = new Request($psrRequest);

        $this->assertEquals('1.1', $request->getProtocolVersion());
        $this->assertSame($stream, $request->getBody());
        $this->assertSame($psrRequest->getHeaders(), $request->getHeaders());
        $this->assertEquals($psrRequest->getRequestTarget(), $request->getRequestTarget());
    }
}
