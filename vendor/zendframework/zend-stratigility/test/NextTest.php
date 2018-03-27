<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @see       http://github.com/zendframework/zend-stratigility for the canonical source repository
 * @copyright Copyright (c) 2015-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-stratigility/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Stratigility;

use Interop\Http\Middleware\DelegateInterface;
use Interop\Http\Middleware\ServerMiddlewareInterface;
use PHPUnit_Framework_Assert as Assert;
use PHPUnit_Framework_TestCase as TestCase;
use Prophecy\Argument;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionProperty;
use SplQueue;
use Zend\Diactoros\ServerRequest as PsrRequest;
use Zend\Diactoros\Response as PsrResponse;
use Zend\Diactoros\Uri;
use Zend\Stratigility\Exception;
use Zend\Stratigility\Http\Request;
use Zend\Stratigility\Http\Response;
use Zend\Stratigility\Next;
use Zend\Stratigility\Route;

class NextTest extends TestCase
{
    protected $errorHandler;

    public function setUp()
    {
        $this->restoreErrorHandler();
        $psrRequest     = new PsrRequest([], [], 'http://example.com/', 'GET', 'php://memory');
        $this->queue    = new SplQueue();
        $this->request  = new Request($psrRequest);
        $this->response = new Response(new PsrResponse());
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

    public function testDoneHandlerIsInvokedWhenQueueIsExhausted()
    {
        // e.g., 0 length array, or all handlers call next
        $triggered = null;
        $done = function ($req, $res, $err = null) use (&$triggered) {
            $triggered = true;
        };

        $next = new Next($this->queue, $done);
        $next($this->request, $this->response);
        $this->assertTrue($triggered);
    }

    public function testDoneHandlerReceivesRequestAndResponse()
    {
        // e.g., 0 length array, or all handlers call next
        $request   = $this->request;
        $response  = $this->response;
        $triggered = null;
        $done = function ($req, $res, $err = null) use ($request, $response, &$triggered) {
            $this->assertSame($request, $req);
            $this->assertSame($response, $response);
            $triggered = true;
        };

        $next = new Next($this->queue, $done);
        $next($request, $response);
        $this->assertTrue($triggered);
    }

    public function testInvokesItselfWhenRouteDoesNotMatchCurrentUrl()
    {
        // e.g., handler matches "/foo", but path is "/bar"
        $route = new Route('/foo', function ($req, $res, $next) {
            $this->fail('Route should not be invoked if path does not match');
        });
        $this->queue->enqueue($route);

        $triggered = null;
        $done = function ($req, $res, $err = null) use (&$triggered) {
            $triggered = true;
        };

        $this->request->withUri(new Uri('http://local.example.com/bar'));

        $next = new Next($this->queue, $done);
        $next($this->request, $this->response);
        $this->assertTrue($triggered);
    }

    public function testInvokesItselfIfRouteDoesNotMatchAtABoundary()
    {
        // e.g., if route is "/foo", but path is "/foobar", no match
        $route = new Route('/foo', function ($req, $res, $next) {
            $this->fail('Route should not be invoked if path does not match');
        });
        $this->queue->enqueue($route);

        $triggered = null;
        $done = function ($req, $res, $err = null) use (&$triggered) {
            $triggered = true;
        };

        $this->request->withUri(new Uri('http://local.example.com/foobar'));

        $next = new Next($this->queue, $done);
        $next($this->request, $this->response);
        $this->assertTrue($triggered);
    }

    public function testInvokesHandlerWhenMatched()
    {
        // e.g., if route is "/foo", but path is "/foobar", no match
        $triggered = null;
        $route = new Route('/foo', function ($req, $res, $next) use (&$triggered) {
            $triggered = true;
        });
        $this->queue->enqueue($route);

        $done = function ($req, $res, $err = null) {
            $this->fail('Should not hit done handler');
        };

        $request = $this->request->withUri(new Uri('http://local.example.com/foo'));

        $next = new Next($this->queue, $done);
        $next($request, $this->response);
        $this->assertTrue($triggered);
    }

    public function testRequestUriInInvokedHandlerDoesNotContainMatchedPortionOfRoute()
    {
        // e.g., if route is "/foo", and "/foo/bar" is the original path,
        // then the URI path in the handler is "/bar"
        $triggered = null;
        $route = new Route('/foo', function ($req, $res, $next) use (&$triggered) {
            $triggered = $req->getUri()->getPath();
        });
        $this->queue->enqueue($route);

        $done = function ($req, $res, $err = null) {
            $this->fail('Should not hit done handler');
        };

        $request = $this->request->withUri(new Uri('http://local.example.com/foo/bar'));

        $next = new Next($this->queue, $done);
        $next($request, $this->response);
        $this->assertEquals('/bar', $triggered);
    }

    public function testSlashAndPathGetResetBeforeExecutingNextMiddleware()
    {
        $route1 = new Route('/foo', function ($req, $res, $next) {
            $next($req, $res);
        });
        $route2 = new Route('/foo/bar', function ($req, $res, $next) {
            $next($req, $res);
        });
        $route3 = new Route('/foo/baz', function ($req, $res, $next) {
            $res->getBody()->write('done');
            return $res;
        });

        $this->queue->enqueue($route1);
        $this->queue->enqueue($route2);
        $this->queue->enqueue($route3);

        $done = function ($req, $res, $err) {
            $this->fail('Should not hit final handler');
        };

        $request = $this->request->withUri(new Uri('http://example.com/foo/baz/bat'));
        $next = new Next($this->queue, $done);
        $next($request, $this->response);
        $this->assertEquals('done', (string) $this->response->getBody());
    }

    public function testMiddlewareReturningResponseShortcircuits()
    {
        $route1 = new Route('/foo', function ($req, $res, $next) {
            return $res;
        });
        $route2 = new Route('/foo/bar', function ($req, $res, $next) {
            $next($req, $res);
            $this->fail('Should not hit route2 handler');
        });
        $route3 = new Route('/foo/baz', function ($req, $res, $next) {
            $next($req, $res);
            $this->fail('Should not hit route3 handler');
        });

        $this->queue->enqueue($route1);
        $this->queue->enqueue($route2);
        $this->queue->enqueue($route3);

        $done = function ($req, $res, $err) {
            $this->fail('Should not hit final handler');
        };

        $request = $this->request->withUri(new Uri('http://example.com/foo/bar/baz'));
        $next = new Next($this->queue, $done);
        $result = $next($request, $this->response);
        $this->assertSame($this->response, $result);
    }

    public function testMiddlewareCallingNextWithResponseAsFirstArgumentResetsResponse()
    {
        $cannedResponse = new Response(new PsrResponse());
        $triggered = false;

        $route1 = new Route('/foo', function ($req, $res, $next) use ($cannedResponse) {
            return $next($req, $cannedResponse);
        });
        $route2 = new Route('/foo/bar', function ($req, $res, $next) use ($cannedResponse, &$triggered) {
            $this->assertSame($cannedResponse, $res);
            $triggered = true;
        });

        $this->queue->enqueue($route1);
        $this->queue->enqueue($route2);

        $done = function ($req, $res, $err) {
            $this->fail('Should not hit final handler');
        };

        $request = $this->request->withUri(new Uri('http://example.com/foo/bar/baz'));
        $next = new Next($this->queue, $done);
        $result = $next($request, $this->response);
        $this->assertTrue($triggered);
        $this->assertSame($cannedResponse, $result);
    }

    public function testMiddlewareCallingNextWithRequestPassesRequestToNextMiddleware()
    {
        $request       = $this->request->withUri(new Uri('http://example.com/foo/bar/baz'));
        $cannedRequest = clone $request;
        $cannedRequest = $cannedRequest->withMethod('POST');

        $route1 = new Route('/foo/bar', function ($req, $res, $next) use ($cannedRequest) {
            return $next($cannedRequest, $res);
        });
        $route2 = new Route('/foo/bar/baz', function ($req, $res, $next) use ($cannedRequest) {
            $this->assertEquals($cannedRequest->getMethod(), $req->getMethod());
            return $res;
        });

        $this->queue->enqueue($route1);
        $this->queue->enqueue($route2);

        $done = function ($req, $res, $err) {
            $this->fail('Should not hit final handler');
        };

        $next = new Next($this->queue, $done);
        $next($request, $this->response);
    }

    public function testMiddlewareCallingNextWithResponseResetsResponse()
    {
        $cannedResponse = new Response(new PsrResponse());

        $route1 = new Route('/foo', function ($req, $res, $next) use ($cannedResponse) {
            return $next($req, $cannedResponse);
        });
        $route2 = new Route('/foo/bar', function ($req, $res, $next) use ($cannedResponse) {
            $this->assertSame($cannedResponse, $res);
            return $res;
        });

        $this->queue->enqueue($route1);
        $this->queue->enqueue($route2);

        $done = function ($req, $res, $err) {
            $this->fail('Should not hit final handler');
        };

        $request = $this->request->withUri(new Uri('http://example.com/foo/bar/baz'));
        $next = new Next($this->queue, $done);
        $next($request, $this->response);
    }

    public function testNextShouldReturnPassedResponseWhenNoReturnValueProvided()
    {
        $cannedResponse = new Response(new PsrResponse());

        $route1 = new Route('/foo', function ($req, $res, $next) use ($cannedResponse) {
            $next($req, $cannedResponse);
        });
        $route2 = new Route('/foo/bar', function ($req, $res, $next) use ($cannedResponse) {
            $this->assertSame($cannedResponse, $res);
            return $res;
        });

        $this->queue->enqueue($route1);
        $this->queue->enqueue($route2);

        $done = function ($req, $res, $err) {
            $this->fail('Should not hit final handler');
        };

        $request = $this->request->withUri(new Uri('http://example.com/foo/bar/baz'));
        $next    = new Next($this->queue, $done);
        $result  = $next($request, $this->response);
        $this->assertSame($this->response, $result);
    }

    /**
     * @group 25
     */
    public function testNextShouldCloneQueueOnInstantiation()
    {
        $done = function ($req, $res, $err) {
            $this->fail('Should not hit final handler');
        };
        $next = new Next($this->queue, $done);

        $r = new ReflectionProperty($next, 'queue');
        $r->setAccessible(true);
        $queue = $r->getValue($next);

        $this->assertNotSame($this->queue, $queue);
        $this->assertEquals($this->queue, $queue);
    }

    /**
     * @todo Remove with 2.0.0
     */
    public function testNextShouldRaiseDeprecationNoticeWhenInvokedWithErrorArgument()
    {
        $route = new Route('/', function ($err, $req, $res, $next) {
            return $this->response;
        });
        $this->queue->enqueue($route);

        $done = function ($req, $res, $err) {
            $this->fail('Should not hit final handler');
        };
        $next = new Next($this->queue, $done);

        set_error_handler(function ($errno, $errmsg) {
            $this->assertContains('Usage of error middleware is deprecated', $errmsg);
        }, E_USER_DEPRECATED);
        $result = $next($this->request, $this->response, 'Error');
        restore_error_handler();

        $this->assertSame($this->response, $result);
    }

    /**
     * @group http-interop
     */
    public function testNextImplementsDelegateInterface()
    {
        $next = new Next($this->queue, function () {
        });

        $this->assertInstanceOf(DelegateInterface::class, $next);
    }

    /**
     * @todo Remove with 2.0.0
     * @group http-interop
     */
    public function testNextDoesNotComposeResponsePrototypeByDefault()
    {
        $next = new Next($this->queue, function () {
        });

        $this->assertAttributeEmpty('responsePrototype', $next);
    }

    /**
     * @todo Remove with 2.0.0
     * @group http-interop
     */
    public function testNextCanComposeAResponsePrototype()
    {
        $response = $this->prophesize(ResponseInterface::class)->reveal();
        $next = new Next($this->queue, function () {
        });
        $next->setResponsePrototype($response);

        $this->assertAttributeSame($response, 'responsePrototype', $next);
    }

    /**
     * @todo Remove with 2.0.0
     * @group http-interop
     */
    public function testInvocationWillSetResponsePrototypeIfNotAlreadySet()
    {
        $response = $this->prophesize(ResponseInterface::class)->reveal();
        $request = $this->request->withUri(new Uri('http://local.example.com/foo'));

        $route = new Route('/foo', function ($req, $res, $next) {
            return $res;
        });
        $this->queue->enqueue($route);

        $next = new Next($this->queue, function () {
            Assert::fail('Done argument called, when it should not have been');
        });

        $this->assertSame($response, $next($request, $response));

        $this->assertAttributeSame($response, 'responsePrototype', $next);

        $r = new ReflectionProperty($next, 'dispatch');
        $r->setAccessible(true);
        $dispatch = $r->getValue($next);
        $this->assertAttributeSame($response, 'responsePrototype', $dispatch);
    }

    /**
     * @todo Remove with 2.0.0
     * @group http-interop
     */
    public function testDoneHandlerIsInvokedWhenQueueIsExhaustedByProcessAndResponsePrototypeIsPresent()
    {
        $response = $this->prophesize(ResponseInterface::class)->reveal();

        // e.g., 0 length array, or all handlers call next
        $done = function ($req, $res, $err = null) use ($response) {
            Assert::assertSame($response, $res);
            return $response;
        };

        $next = new Next($this->queue, $done);
        $next->setResponsePrototype($response);

        $this->assertSame($response, $next->process($this->request));
    }

    /**
     * @todo Remove with 2.0.0
     * @group http-interop
     */
    public function testProcessRaisesExceptionPriorToCallingDoneHandlerIfNoResponsePrototypePresent()
    {
        $done = function ($req, $res, $err = null) {
            Assert::fail('Reached $done handler, and should not have.');
        };

        $next = new Next($this->queue, $done);

        $this->setExpectedException(Exception\MissingResponsePrototypeException::class);
        $next->process($this->request);
    }

    /**
     * @todo Remove with 2.0.0
     * @group http-interop
     */
    public function testProcessRaisesExceptionPriorToCallingDoneHandlerIfNotAServerRequest()
    {
        $request = $this->prophesize(RequestInterface::class);
        $request->getUri()->shouldNotBeCalled();

        $done = function ($req, $res, $err = null) {
            Assert::fail('Reached $done handler, and should not have.');
        };

        $next = new Next($this->queue, $done);
        $next->setResponsePrototype($this->prophesize(ResponseInterface::class)->reveal());

        $this->setExpectedException(Exception\InvalidRequestTypeException::class);
        $next->process($request->reveal());
    }

    /**
     * @todo Remove the $done argument during setup for 2.0.0
     * @group http-interop
     */
    public function testProcessReinvokesItselfWhenRouteDoesNotMatchCurrentUrl()
    {
        // e.g., handler matches "/foo", but path is "/bar"
        $done = function ($req, $res, $err = null) {
            Assert::fail('Should not have hit the done handler, but did, with error: ' . var_export($err, true));
        };
        $request = $this->request->withUri(new Uri('http://local.example.com/bar'));
        $response = $this->prophesize(ResponseInterface::class)->reveal();

        $first = $this->prophesize(ServerMiddlewareInterface::class);
        $first
            ->process($request, Argument::type(Next::class))
            ->will(function () {
                // This one should be skipped
                Assert::fail('Route should not be invoked if path does not match');
            });
        $this->queue->enqueue(new Route('/foo', $first->reveal()));

        $second = $this->prophesize(ServerMiddlewareInterface::class);
        $second
            ->process(Argument::type(RequestInterface::class), Argument::type(Next::class))
            ->willReturn($response);
        $this->queue->enqueue(new Route('/bar', $second->reveal()));

        $next = new Next($this->queue, $done);
        $next->setResponsePrototype($response);

        $this->assertSame($response, $next->process($request));
    }

    /**
     * @todo Remove the $done argument during setup for 2.0.0
     * @group http-interop
     */
    public function testProcessReinvokesItselfIfRouteDoesNotMatchAtABoundary()
    {
        // e.g., if route is "/foo", but path is "/foobar", no match
        $done = function ($req, $res, $err = null) {
            Assert::fail('Should not have hit the done handler, but did');
        };
        $request = $this->request->withUri(new Uri('http://local.example.com/foobar'));
        $response = $this->prophesize(ResponseInterface::class)->reveal();

        $first = $this->prophesize(ServerMiddlewareInterface::class);
        $first
            ->process($request, Argument::type(Next::class))
            ->will(function () {
                // This one should be skipped
                Assert::fail('Route should not be invoked if path does not match');
            });
        $this->queue->enqueue(new Route('/foo', $first->reveal()));

        $second = $this->prophesize(ServerMiddlewareInterface::class);
        $second
            ->process(Argument::type(RequestInterface::class), Argument::type(Next::class))
            ->willReturn($response);
        $this->queue->enqueue(new Route('/foobar', $second->reveal()));

        $next = new Next($this->queue, $done);
        $next->setResponsePrototype($response);
        $this->assertSame($response, $next->process($request));
    }

    /**
     * @todo Remove the $done argument during setup for 2.0.0
     * @group http-interop
     */
    public function testProcessDispatchesHandlerWhenMatched()
    {
        $done = function ($req, $res, $err = null) {
            Assert::fail('Should not have hit the done handler, but did');
        };
        $request = $this->request->withUri(new Uri('http://local.example.com/foo'));
        $response = $this->prophesize(ResponseInterface::class)->reveal();

        $middleware = $this->prophesize(ServerMiddlewareInterface::class);
        $middleware
            ->process(Argument::type(RequestInterface::class), Argument::type(Next::class))
            ->willReturn($response);
        $this->queue->enqueue(new Route('/foo', $middleware->reveal()));

        $next = new Next($this->queue, $done);
        $next->setResponsePrototype($response);
        $this->assertSame($response, $next->process($request));
    }

    /**
     * @todo Remove the $done argument during setup for 2.0.0
     * @group http-interop
     */
    public function testRequestUriInHandlerInvokedByProcessDoesNotContainMatchedPortionOfRoute()
    {
        // e.g., if route is "/foo", and "/foo/bar" is the original path,
        // then the URI path in the handler is "/bar"
        $done = function ($req, $res, $err = null) {
            Assert::fail('Should not have hit the done handler, but did');
        };
        $request = $this->request->withUri(new Uri('http://local.example.com/foo/bar'));
        $response = $this->prophesize(ResponseInterface::class)->reveal();

        $middleware = $this->prophesize(ServerMiddlewareInterface::class);
        $middleware
            ->process(Argument::that(function ($arg) {
                Assert::assertInstanceOf(RequestInterface::class, $arg);
                Assert::assertEquals('/bar', $arg->getUri()->getPath());
                return true;
            }), Argument::type(Next::class))
            ->willReturn($response);
        $this->queue->enqueue(new Route('/foo', $middleware->reveal()));

        $next = new Next($this->queue, $done);
        $next->setResponsePrototype($response);
        $this->assertSame($response, $next->process($request));
    }

    /**
     * @todo Remove the $done argument during setup for 2.0.0
     * @group http-interop
     */
    public function testSlashAndPathGetResetByProcessBeforeExecutingNextMiddleware()
    {
        $done = function ($req, $res, $err = null) {
            Assert::fail('Should not have hit the done handler, but did');
        };
        $request = $this->request->withUri(new Uri('http://example.com/foo/baz/bat'));
        $response = $this->prophesize(ResponseInterface::class)->reveal();

        $route1 = $this->prophesize(ServerMiddlewareInterface::class);
        $route1
            ->process(Argument::type(RequestInterface::class), Argument::type(Next::class))
            ->will(function ($args) {
                $request = $args[0];
                $next = $args[1];
                return $next->process($request);
            });
        $this->queue->enqueue(new Route('/foo', $route1->reveal()));

        $route2 = $this->prophesize(ServerMiddlewareInterface::class);
        $route2
            ->process(Argument::type(RequestInterface::class), Argument::type(Next::class))
            ->shouldNotBeCalled();
        $this->queue->enqueue(new Route('/foo/bar', $route2->reveal()));

        $route3 = $this->prophesize(ServerMiddlewareInterface::class);
        $route3
            ->process(Argument::that(function ($arg) {
                Assert::assertEquals('/bat', $arg->getUri()->getPath());
                return true;
            }), Argument::type(Next::class))
            ->willReturn($response);
        $this->queue->enqueue(new Route('/foo/baz', $route3->reveal()));

        $next = new Next($this->queue, $done);
        $next->setResponsePrototype($response);
        $this->assertSame($response, $next->process($request));
    }

    /**
     * @todo Remove the $done argument during setup for 2.0.0
     * @group http-interop
     */
    public function testMiddlewareReturningResponseShortCircuitsProcess()
    {
        $done = function ($req, $res, $err = null) {
            Assert::fail('Should not have hit the done handler, but did');
        };
        $request = $this->request->withUri(new Uri('http://example.com/foo/bar/baz'));
        $response = $this->prophesize(ResponseInterface::class)->reveal();

        $route1 = $this->prophesize(ServerMiddlewareInterface::class);
        $route1
            ->process(Argument::that(function ($arg) {
                Assert::assertEquals('/bar/baz', $arg->getUri()->getPath());
                return true;
            }), Argument::type(Next::class))
            ->willReturn($response);
        $this->queue->enqueue(new Route('/foo', $route1->reveal()));

        $route2 = $this->prophesize(ServerMiddlewareInterface::class);
        $route2
            ->process(Argument::type(RequestInterface::class), Argument::type(Next::class))
            ->shouldNotBeCalled();
        $this->queue->enqueue(new Route('/foo/bar', $route2->reveal()));

        $next = new Next($this->queue, $done);
        $next->setResponsePrototype($this->response);
        $this->assertSame($response, $next->process($request));
    }

    /**
     * @todo Remove the $done argument during setup for 2.0.0
     * @group http-interop
     */
    public function testProcessReturnsResponsePrototypeIfNoResponseReturnedByMiddleware()
    {
        $done = function ($req, $res, $err = null) {
            Assert::fail('Should not have hit the done handler, but did');
        };
        $request = $this->request->withUri(new Uri('http://example.com/foo/bar/baz'));
        $response = $this->prophesize(ResponseInterface::class)->reveal();

        $route1 = $this->prophesize(ServerMiddlewareInterface::class);
        $route1
            ->process(Argument::that(function ($arg) {
                Assert::assertEquals('/bar/baz', $arg->getUri()->getPath());
                return true;
            }), Argument::type(Next::class))
            ->willReturn('foobar');
        $this->queue->enqueue(new Route('/foo', $route1->reveal()));

        $route2 = $this->prophesize(ServerMiddlewareInterface::class);
        $route2
            ->process(Argument::type(RequestInterface::class), Argument::type(Next::class))
            ->shouldNotBeCalled();
        $this->queue->enqueue(new Route('/foo/bar', $route2->reveal()));

        $next = new Next($this->queue, $done);
        $next->setResponsePrototype($response);
        $this->assertSame($response, $next->process($request));
    }

    /**
     * @todo Remove the $done argument during setup for 2.0.0
     * @group http-interop
     */
    public function testProcessRaisesExceptionIfNoResponseReturnedByMiddlewareAndNoResponsePrototypePresent()
    {
        $done = function ($req, $res, $err = null) {
            Assert::fail('Should not have hit the done handler, but did');
        };
        $request = $this->request->withUri(new Uri('http://example.com/foo/bar/baz'));

        $route1 = $this->prophesize(ServerMiddlewareInterface::class);
        $route1
            ->process(Argument::that(function ($arg) {
                Assert::assertEquals('/bar/baz', $arg->getUri()->getPath());
                return true;
            }, Argument::type(Next::class)))
            ->willReturn('foobar');
        $this->queue->enqueue(new Route('/foo', $route1->reveal()));

        $next = new Next($this->queue, $done);

        $this->setExpectedException(Exception\MissingResponsePrototypeException::class);
        $next->process($request);
    }

    /**
     * @todo Remove for 2.0.0, as the $done handler will no longer be used.
     * @group http-interop
     */
    public function testNextCanUseADelegateForTheDoneHandler()
    {
        $delegate = $this->prophesize(DelegateInterface::class);
        $delegate
            ->process(Argument::type(RequestInterface::class))
            ->willReturn('FOOBAR');

        $next = new Next($this->queue, $delegate->reveal());
        $this->assertEquals('FOOBAR', $next->process($this->request));
    }

    /**
     * @todo Remove for 2.0.0; operation should become a no-op at that time.
     * @group error-handling
     */
    public function testSettingRaiseThrowablesFlagSetsCorrespondingFlagOnComposedDispatchInstance()
    {
        $next = new Next($this->queue, $this->prophesize(DelegateInterface::class)->reveal());
        $next->raiseThrowables();

        $this->assertAttributeSame(true, 'raiseThrowables', $next);

        $r = new ReflectionProperty($next, 'dispatch');
        $r->setAccessible(true);
        $dispatch = $r->getValue($next);
        $this->assertAttributeSame(true, 'raiseThrowables', $dispatch);
    }

    public function throwablesProvider()
    {
        if (class_exists('Error')) {
            yield 'throwable' => [ new \Error() ];
        }

        yield 'exception' => [ new \Exception() ];
    }

    /**
     * @todo Remove for 2.0.0; $err goes away in that version.
     * @dataProvider throwablesProvider
     * @group error-handling
     */
    public function testEnablingRaiseThrowablesFlagWillCauseInvocationToRaiseThrowableForThrowableErrorArguments(
        $throwable
    ) {
        $next = new Next($this->queue, $this->prophesize(DelegateInterface::class)->reveal());
        $next->raiseThrowables();

        try {
            $next(
                $this->request,
                $this->response,
                $throwable
            );
            $this->fail('Throwable not raised when it was expected');
        } catch (\Throwable $e) {
            $this->assertSame($throwable, $e);
        } catch (\Exception $e) {
            $this->assertSame($throwable, $e);
        }
    }

    /**
     * @todo Remove for 2.0.0; $err goes away in that version.
     * @group error-handling
     */
    public function testEnablingRaiseThrowablesFlagWillCauseInvocationToRaiseMiddlewareExceptionForStringArgument()
    {
        $next = new Next($this->queue, $this->prophesize(DelegateInterface::class)->reveal());
        $next->raiseThrowables();

        $triggered = false;
        $this->errorHandler = set_error_handler(function ($errno, $errstr) use (&$triggered) {
            $this->assertContains('error middleware is deprecated', $errstr);
            $triggered = true;
            return true;
        }, E_USER_DEPRECATED);

        try {
            $next(
                $this->request,
                $this->response,
                'Throwable'
            );
            $this->fail('Throwable not raised when it was expected');
        } catch (Exception\MiddlewareException $e) {
            $this->assertEquals('Throwable', $e->getMessage());
        } catch (\Throwable $e) {
            $this->fail(sprintf(
                'Caught unexpected throwable: %s',
                $e->getMessage()
            ));
        } catch (\Exception $e) {
            $this->fail(sprintf(
                'Caught unexpected exception: %s',
                $e->getMessage()
            ));
        }

        $this->assertTrue($triggered, 'Deprecation notice not triggered');
    }

    public function nonNullNonThrowableNonStringErrors()
    {
        return [
            'false'      => [false],
            'true'       => [true],
            'zero'       => [0],
            'int'        => [1],
            'zero-float' => [0.0],
            'float'      => [1.1],
            'array'      => [[0, 1, 2]],
            'object'     => [(object) ['value' => 'message']],
        ];
    }

    /**
     * @todo Remove for 2.0.0; $err goes away in that version.
     * @group error-handling
     * @dataProvider nonNullNonThrowableNonStringErrors
     */
    public function testEnablingRaiseThrowablesFlagWillCauseInvocationToRaiseMiddlewareExceptionForNonNullArguments(
        $error
    ) {
        $next = new Next($this->queue, $this->prophesize(DelegateInterface::class)->reveal());
        $next->raiseThrowables();

        $triggered = false;
        $this->errorHandler = set_error_handler(function ($errno, $errstr) use (&$triggered) {
            $this->assertContains('error middleware is deprecated', $errstr);
            $triggered = true;
            return true;
        }, E_USER_DEPRECATED);

        switch (true) {
            case (is_object($error)):
                $expected = get_class($error);
                break;
            case (is_array($error)):
                $expected = gettype($error);
                break;
            case (is_scalar($error)):
                // fall-through
            default:
                $expected = var_export($error, true);
                break;
        }

        try {
            $next(
                $this->request,
                $this->response,
                $error
            );
            $this->fail('Throwable not raised when it was expected');
        } catch (Exception\MiddlewareException $e) {
            $this->assertContains($expected, $e->getMessage());
        } catch (\Throwable $e) {
            $this->fail(sprintf(
                'Caught unexpected throwable: %s',
                $e->getMessage()
            ));
        } catch (\Exception $e) {
            $this->fail(sprintf(
                'Caught unexpected exception: %s',
                $e->getMessage()
            ));
        }

        $this->assertTrue($triggered, 'Deprecation notice not triggered');
    }

    /**
     * @todo Remove for 2.0.0
     * @group error-handling
     */
    public function testNestedNextInvocationWithAnErrorShouldDispatchErrorMiddleware()
    {
        $internalQueue = clone $this->queue;
        $internalQueue->enqueue(new Route('/', function ($request, $response, $next) {
            return $next($request, $response, 'ERROR');
        }));

        $nextDelegateQueue = clone $this->queue;
        $nextDelegateQueue->enqueue(new Route('/', function ($err, $request, $response, $next) {
            $response->getBody()->write('ERROR DETECTED');
            return $response->withStatus(599);
        }));

        $finalDelegate = $this->prophesize(DelegateInterface::class);
        $finalDelegate->process(Argument::any())->shouldNotBeCalled();

        $nextDelegate = new Next($nextDelegateQueue, $finalDelegate->reveal());
        $internalNext = new Next($internalQueue, $nextDelegate);

        set_error_handler(function ($errno, $errstr) {
            return false !== strstr($errstr, 'error middleware is deprecated');
        }, E_USER_DEPRECATED);

        $response = $internalNext($this->request, $this->response);

        restore_error_handler();

        $this->assertEquals(599, $response->getStatusCode());
        $this->assertEquals('ERROR DETECTED', (string) $response->getBody());
    }
}
