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
use RuntimeException;
use stdClass;
use TypeError;
use Zend\Stratigility\Dispatch;
use Zend\Stratigility\ErrorMiddlewareInterface;
use Zend\Stratigility\Exception;
use Zend\Stratigility\Http;
use Zend\Stratigility\MiddlewarePipe;
use Zend\Stratigility\Next;
use Zend\Stratigility\Route;

class DispatchTest extends TestCase
{
    private $request;

    private $response;

    public function setUp()
    {
        $this->request = $this->prophesize(ServerRequestInterface::class);
        $this->response = $this->prophesize(ResponseInterface::class);
    }

    public function testHasErrorAndHandleArityIsFourTriggersHandler()
    {
        $triggered = false;

        $handler = function ($err, $req, $res, $next) use (&$triggered) {
            $triggered = $err;
        };
        $next = function ($req, $res, $err) {
            Assert::fail('Next was called; it should not have been');
        };

        $route = new Route('/foo', $handler);
        $dispatch = new Dispatch();
        $err = (object) ['error' => true];
        $dispatch($route, $err, $this->request->reveal(), $this->response->reveal(), $next);
        $this->assertSame($err, $triggered);
    }

    public function testHasErrorAndHandleArityLessThanFourTriggersNextWithError()
    {
        $triggered = false;

        $handler = function ($req, $res, $next) {
            Assert::fail('Handler was called; it should not have been');
        };
        $next = function ($req, $res, $err) use (&$triggered) {
            $triggered = $err;
        };

        $route = new Route('/foo', $handler);
        $dispatch = new Dispatch();
        $err = (object) ['error' => true];
        $dispatch($route, $err, $this->request->reveal(), $this->response->reveal(), $next);
        $this->assertSame($err, $triggered);
    }

    public function testNoErrorAndHandleArityGreaterThanThreeTriggersNext()
    {
        $triggered = false;

        $handler = function ($err, $req, $res, $next) {
            Assert::fail('Handler was called; it should not have been');
        };
        $next = function ($req, $res, $err) use (&$triggered) {
            $triggered = $err;
        };

        $route = new Route('/foo', $handler);
        $dispatch = new Dispatch();
        $err = null;
        $dispatch($route, $err, $this->request->reveal(), $this->response->reveal(), $next);
        $this->assertSame($err, $triggered);
    }

    public function testNoErrorAndHandleArityLessThanFourTriggersHandler()
    {
        $triggered = false;

        $handler = function ($req, $res, $next) use (&$triggered) {
            $triggered = $req;
        };
        $next = function ($req, $res, $err) {
            Assert::fail('Next was called; it should not have been');
        };

        $route = new Route('/foo', $handler);
        $dispatch = new Dispatch();
        $err = null;
        $dispatch($route, $err, $this->request->reveal(), $this->response->reveal(), $next);
        $this->assertSame($this->request->reveal(), $triggered);
    }

    public function testThrowingExceptionInErrorHandlerTriggersNextWithException()
    {
        $exception = new RuntimeException;
        $triggered = null;

        $handler = function ($err, $req, $res, $next) use ($exception) {
            throw $exception;
        };
        $next = function ($req, $res, $err) use (&$triggered) {
            $triggered = $err;
        };

        $route = new Route('/foo', $handler);
        $dispatch = new Dispatch();
        $err = (object) ['error' => true];
        $dispatch($route, $err, $this->request->reveal(), $this->response->reveal(), $next);
        $this->assertSame($exception, $triggered);
    }

    public function testThrowingExceptionInNonErrorHandlerTriggersNextWithException()
    {
        $exception = new RuntimeException;
        $triggered = null;

        $handler = function ($req, $res, $next) use ($exception) {
            throw $exception;
        };
        $next = function ($req, $res, $err) use (&$triggered) {
            $triggered = $err;
        };

        $route = new Route('/foo', $handler);
        $dispatch = new Dispatch();
        $err = null;
        $dispatch($route, $err, $this->request->reveal(), $this->response->reveal(), $next);
        $this->assertSame($exception, $triggered);
    }

    public function testReturnsValueFromNonErrorHandler()
    {
        $handler = function ($req, $res, $next) {
            return $res;
        };
        $next = function ($req, $res, $err) {
            Assert::fail('Next was called; it should not have been');
        };

        $route = new Route('/foo', $handler);
        $dispatch = new Dispatch();
        $err = null;
        $result = $dispatch($route, $err, $this->request->reveal(), $this->response->reveal(), $next);
        $this->assertSame($this->response->reveal(), $result);
    }

    public function testIfErrorHandlerReturnsResponseDispatchReturnsTheResponse()
    {
        $handler = function ($err, $req, $res, $next) {
            return $res;
        };
        $next = function ($req, $res, $err) {
            Assert::fail('Next was called; it should not have been');
        };

        $route = new Route('/foo', $handler);
        $dispatch = new Dispatch();
        $err = (object) ['error' => true];
        $result = $dispatch($route, $err, $this->request->reveal(), $this->response->reveal(), $next);
        $this->assertSame($this->response->reveal(), $result);
    }

    /**
     * @group 28
     */
    public function testShouldAllowDispatchingPsr7Instances()
    {
        $handler = function ($req, $res, $next) {
            return $res;
        };
        $next = function ($req, $res, $err) {
            Assert::fail('Next was called; it should not have been');
        };

        $request  = $this->prophesize(ServerRequestInterface::class);
        $response = $this->prophesize(ResponseInterface::class);
        $dispatch = new Dispatch();
        $route    = new Route('/foo', $handler);
        $err      = null;
        $result = $dispatch($route, $err, $request->reveal(), $response->reveal(), $next);
        $this->assertSame($response->reveal(), $result);
    }

    /**
     * @requires PHP 7.0
     * @group 37
     */
    public function testWillCatchPhp7Throwable()
    {
        $callableWithHint = function (stdClass $parameter) {
            // will not be executed
        };

        $middleware = function ($req, $res, $next) use ($callableWithHint) {
            $callableWithHint('not an stdClass');
        };

        // Using PHPUnit mock here to allow asserting that the method is called.
        // Prophecy doesn't allow defining arbitrary methods on mocks it generates.
        $errorHandler = $this->getMockBuilder('stdClass')
            ->setMethods(['__invoke'])
            ->getMock();
        $errorHandler
            ->expects(self::once())
            ->method('__invoke')
            ->with(
                $this->request->reveal(),
                $this->response->reveal(),
                self::callback(function (TypeError $throwable) {
                    self::assertStringStartsWith(
                        'Argument 1 passed to ZendTest\Stratigility\DispatchTest::ZendTest\Stratigility\{closure}()'
                        . ' must be an instance of stdClass, string given',
                        $throwable->getMessage()
                    );

                    return true;
                })
            );

        $dispatch = new Dispatch();

        $dispatch(
            new Route('/foo', $middleware),
            null,
            $this->request->reveal(),
            $this->response->reveal(),
            $errorHandler
        );
    }

    /**
     * @group http-interop
     */
    public function testResponsePrototypeIsAbsentByDefault()
    {
        $dispatch = new Dispatch();
        $this->assertAttributeEmpty('responsePrototype', $dispatch);
    }

    /**
     * @group http-interop
     */
    public function testCanInjectAResponsePrototype()
    {
        $dispatch = new Dispatch();
        $dispatch->setResponsePrototype($this->response->reveal());
        $this->assertAttributeSame($this->response->reveal(), 'responsePrototype', $dispatch);
    }

    /**
     * @group http-interop
     */
    public function testProcessRaisesExceptionForNonInteropHandlersWhenNoResponsePrototypeIsPresent()
    {
        $handler = function ($req, $res, $next) use (&$triggered) {
            Assert::fail('Handler was called; it should not have been');
        };
        $next = function ($req, $res, $err) {
            Assert::fail('Next was called; it should not have been');
        };
        $route = new Route('/foo', $handler);
        $dispatch = new Dispatch();

        $this->setExpectedException(Exception\MissingResponsePrototypeException::class);
        $dispatch->process($route, $this->request->reveal(), $next);
    }

    /**
     * @group http-interop
     */
    public function testProcessRaisesExceptionForNonInteropHandlersWhenNotProvidedAServerRequest()
    {
        $request = $this->prophesize(RequestInterface::class)->reveal();
        $handler = function ($req, $res, $next) use (&$triggered) {
            Assert::fail('Handler was called; it should not have been');
        };
        $next = function ($req, $res, $err) {
            Assert::fail('Next was called; it should not have been');
        };
        $route = new Route('/foo', $handler);
        $dispatch = new Dispatch();
        $dispatch->setResponsePrototype($this->response->reveal());

        $this->setExpectedException(Exception\InvalidRequestTypeException::class);
        $dispatch->process($route, $request, $next);
    }

    /**
     * @group http-interop
     */
    public function testProcessUsesResponsePrototypeForNonInteropHandlerWhenPresent()
    {
        $handler = function ($req, $res, $next) {
            return $res;
        };
        $next = function ($req, $res, $err) {
            Assert::fail('Next was called; it should not have been');
        };
        $route = new Route('/foo', $handler);
        $dispatch = new Dispatch();
        $dispatch->setResponsePrototype($this->response->reveal());

        $this->assertSame($this->response->reveal(), $dispatch->process($route, $this->request->reveal(), $next));
    }

    /**
     * @group http-interop
     */
    public function testProcessRaisesExceptionWhenCatchingAnExceptionAndNoResponsePrototypePresent()
    {
        $next = $this->prophesize(Next::class);
        $next->willImplement(DelegateInterface::class);

        $request = $this->prophesize(ServerRequestInterface::class)->reveal();

        $exception = new RuntimeException();

        $middleware = $this->prophesize(ServerMiddlewareInterface::class);
        $middleware
            ->process($request, Argument::that([$next, 'reveal']))
            ->willThrow($exception);

        $route = new Route('/foo', $middleware->reveal());

        $dispatch = new Dispatch();

        try {
            $dispatch->process($route, $request, $next->reveal());
            $this->fail('Dispatch::process succeeded when it should have raised an exception');
        } catch (Exception\MissingResponsePrototypeException $e) {
            $this->assertSame($exception, $e->getPrevious(), $e);
        } catch (\Throwable $e) {
            $this->fail(sprintf(
                'Expected MissingResponsePrototypeException; received %s',
                get_class($e)
            ));
        } catch (\Exception $e) {
            $this->fail(sprintf(
                'Expected MissingResponsePrototypeException; received %s',
                get_class($e)
            ));
        }
    }

    /**
     * @group http-interop
     */
    public function testCallingProcessWithInteropMiddlewareDispatchesIt()
    {
        $next = $this->prophesize(Next::class);
        $next->willImplement(DelegateInterface::class);

        $request = $this->prophesize(ServerRequestInterface::class)->reveal();
        $response = $this->prophesize(ResponseInterface::class);

        $middleware = $this->prophesize(ServerMiddlewareInterface::class);
        $middleware
            ->process($request, Argument::that([$next, 'reveal']))
            ->will([$response, 'reveal']);

        $route = new Route('/foo', $middleware->reveal());

        $dispatch = new Dispatch();

        $this->assertSame(
            $response->reveal(),
            $dispatch->process($route, $request, $next->reveal())
        );
    }

    /**
     * @group http-interop
     */
    public function testCallingProcessWithCallableMiddlewareDispatchesIt()
    {
        $next = $this->prophesize(Next::class);

        $request = $this->prophesize(ServerRequestInterface::class)->reveal();
        $response = $this->prophesize(ResponseInterface::class)->reveal();

        $middleware = function ($req, $res, $next) use ($response) {
            return $response;
        };

        $route = new Route('/foo', $middleware);

        $dispatch = new Dispatch();
        $dispatch->setResponsePrototype($this->response->reveal());

        $this->assertSame(
            $response,
            $dispatch->process($route, $request, $next->reveal())
        );
    }

    /**
     * @group http-interop
     */
    public function testInvokingWithInteropMiddlewareDispatchesIt()
    {
        $next = $this->prophesize(Next::class);
        $next->willImplement(DelegateInterface::class);

        $request = $this->prophesize(ServerRequestInterface::class)->reveal();
        $response = $this->prophesize(ResponseInterface::class)->reveal();

        $middleware = $this->prophesize(ServerMiddlewareInterface::class);
        $middleware
            ->process($request, Argument::that([$next, 'reveal']))
            ->willReturn($response);

        $route = new Route('/foo', $middleware->reveal());

        $dispatch = new Dispatch();

        $this->assertSame(
            $response,
            $dispatch($route, null, $request, $this->response->reveal(), $next->reveal())
        );
    }

    public function errorProvider()
    {
        yield 'exception' => [new \Exception('expected')];
        yield 'derivative-exception' => [new RuntimeException('expected')];
        if (version_compare(\PHP_VERSION, '7.0', 'gte')) {
            yield 'throwable' => [new \Error('expected')];
        }
    }

    /**
     * @dataProvider errorProvider
     */
    public function testInvokingWithMiddlewarePipeAndErrorDispatchesNextErrorMiddleware($error)
    {
        $request  = $this->prophesize(ServerRequestInterface::class)->reveal();
        $response = $this->prophesize(ResponseInterface::class)->reveal();
        $expected = $this->prophesize(ResponseInterface::class)->reveal();

        $next = $this->prophesize(Next::class);
        $next->willImplement(DelegateInterface::class);
        $next
            ->__invoke(
                $request,
                $this->response->reveal(),
                $error
            )
            ->willReturn($expected);

        $middleware = $this->prophesize(MiddlewarePipe::class);
        $middleware
            ->__invoke(
                Argument::type(ServerRequestInterface::class),
                Argument::type(ResponseInterface::class),
                Argument::type('callable')
            )
            ->shouldNotBeCalled();
        $middleware
            ->process(
                Argument::type(ServerRequestInterface::class),
                Argument::type(DelegateInterface::class)
            )
            ->shouldNotBeCalled();

        $route = new Route('/foo', $middleware->reveal());

        $dispatch = new Dispatch();

        $this->assertSame(
            $expected,
            $dispatch($route, $error, $request, $this->response->reveal(), $next->reveal())
        );
    }

    public function testInvokingWithMiddlewarePipeAndNoErrorDispatchesAsInteropMiddleware()
    {
        $request  = $this->prophesize(ServerRequestInterface::class)->reveal();
        $response = $this->prophesize(ResponseInterface::class)->reveal();

        $next = $this->prophesize(Next::class);
        $next->willImplement(DelegateInterface::class);
        $next
            ->__invoke(
                Argument::type(ServerRequestInterface::class),
                Argument::type(ResponseInterface::class)
            )
            ->shouldNotBeCalled();
        $next
            ->process(Argument::type(ServerRequestInterface::class))
            ->shouldNotBeCalled();

        $middleware = $this->prophesize(MiddlewarePipe::class);
        $middleware
            ->__invoke(
                Argument::type(ServerRequestInterface::class),
                Argument::type(ResponseInterface::class),
                Argument::type('callable')
            )
            ->shouldNotBeCalled();
        $middleware
            ->hasResponsePrototype()
            ->willReturn(true);
        $middleware
            ->process(
                Argument::type(ServerRequestInterface::class),
                Argument::type(DelegateInterface::class)
            )
            ->willReturn($response);

        $route = new Route('/foo', $middleware->reveal());

        $dispatch = new Dispatch();

        $this->assertSame(
            $response,
            $dispatch($route, null, $request, $this->response->reveal(), $next->reveal())
        );
    }

    /**
     * @group http-interop
     */
    public function testInvokingMemoizesResponseIfNonePreviouslyPresent()
    {
        $next = $this->prophesize(Next::class);

        $request = $this->prophesize(ServerRequestInterface::class)->reveal();
        $response = $this->prophesize(ResponseInterface::class)->reveal();

        $middleware = function ($req, $res, $next) use ($response) {
            return $response;
        };

        $route = new Route('/foo', $middleware);

        $dispatch = new Dispatch();

        $this->assertSame(
            $response,
            $dispatch($route, null, $request, $this->response->reveal(), $next->reveal())
        );

        $this->assertAttributeSame($this->response->reveal(), 'responsePrototype', $dispatch);
    }

    /**
     * @group http-interop
     */
    public function testProcessWillInjectMiddlewarePipeWithResponsePrototypeIfPipelineDoesNotHaveOne()
    {
        $request = $this->prophesize(ServerRequestInterface::class)->reveal();
        $response = $this->prophesize(ResponseInterface::class)->reveal();

        $next = $this->prophesize(Next::class);
        $next->willImplement(DelegateInterface::class);

        $pipeline = $this->prophesize(MiddlewarePipe::class);
        $pipeline->hasResponsePrototype()->willReturn(false);
        $pipeline->setResponsePrototype($response)->shouldBeCalled();
        $pipeline->process($request, $next->reveal())->willReturn($response);

        $dispatch = new Dispatch();
        $dispatch->setResponsePrototype($response);

        $this->assertSame($response, $dispatch->process(
            new Route('/', $pipeline->reveal()),
            $request,
            $next->reveal()
        ));
    }

    /**
     * @group http-interop
     */
    public function testProcessWillNotInjectMiddlewarePipeWithResponsePrototypeIfPipelineAlreadyHasOne()
    {
        $request = $this->prophesize(ServerRequestInterface::class)->reveal();
        $response = $this->prophesize(ResponseInterface::class)->reveal();

        $next = $this->prophesize(Next::class);
        $next->willImplement(DelegateInterface::class);

        $pipeline = $this->prophesize(MiddlewarePipe::class);
        $pipeline->hasResponsePrototype()->willReturn(true);
        $pipeline->setResponsePrototype($response)->shouldNotBeCalled();
        $pipeline->process($request, $next->reveal())->willReturn(null);

        $dispatch = new Dispatch();
        $dispatch->setResponsePrototype($response);

        $this->assertNull($dispatch->process(
            new Route('/', $pipeline->reveal()),
            $request,
            $next->reveal()
        ));
    }

    public function throwablesProvider()
    {
        if (class_exists('Error')) {
            yield 'throwable' => [new \Error()];
        }
        yield 'exception' => [new \Exception()];
    }

    /**
     * @dataProvider throwablesProvider
     * @group error-handling
     */
    public function testThrowsThrowablesRaisedByCallableMiddlewareWhenRaiseThrowablesFlagIsEnabled($throwable)
    {
        $middleware = function () use ($throwable) {
            throw $throwable;
        };

        $route = new Route('/', $middleware);
        $next = $this->prophesize(Next::class);
        $next
            ->__invoke(
                Argument::type(ServerRequestInterface::class),
                Argument::type(ResponseInterface::class),
                $throwable
            )
            ->shouldNotBeCalled();

        $dispatch = new Dispatch();
        $dispatch->raiseThrowables();

        try {
            $dispatch(
                $route,
                null,
                $this->request->reveal(),
                $this->response->reveal(),
                $next->reveal()
            );
            $this->fail('Dispatch succeeded and should not have');
        } catch (\Throwable $e) {
            $this->assertSame($throwable, $e, sprintf(
                'Throwable raised is not the one expected: %s',
                $e->getMessage()
            ));
        } catch (\Exception $e) {
            $this->assertSame($throwable, $e, sprintf(
                'Exception raised is not the one expected: %s',
                $e->getMessage()
            ));
        }
    }

    /**
     * @dataProvider throwablesProvider
     * @group error-handling
     */
    public function testThrowsThrowablesRaisedByErrorMiddlewareWhenRaiseThrowablesFlagIsEnabled($throwable)
    {
        $middleware = $this->prophesize(ErrorMiddlewareInterface::class);
        $middleware
            ->__invoke(
                $throwable,
                Argument::type(ServerRequestInterface::class),
                Argument::type(ResponseInterface::class),
                Argument::type(Next::class)
            )
            ->will(function () use ($throwable) {
                throw $throwable;
            });

        $route = new Route('/', $middleware->reveal());
        $next = $this->prophesize(Next::class);
        $next
            ->__invoke(
                Argument::type(ServerRequestInterface::class),
                Argument::type(ResponseInterface::class),
                $throwable
            )
            ->shouldNotBeCalled();

        $dispatch = new Dispatch();
        $dispatch->raiseThrowables();

        try {
            $dispatch(
                $route,
                $throwable,
                $this->request->reveal(),
                $this->response->reveal(),
                $next->reveal()
            );
            $this->fail('Dispatch succeeded and should not have');
        } catch (\Throwable $e) {
            $this->assertSame($throwable, $e, sprintf(
                'Throwable raised is not the one expected: %s',
                $e->getMessage()
            ));
        } catch (\Exception $e) {
            $this->assertSame($throwable, $e, sprintf(
                'Exception raised is not the one expected: %s',
                $e->getMessage()
            ));
        }
    }

    public function nonStandardMiddlewareProvider()
    {
        $middlewares = [
            'too-few' => function () {
            },
            'too-many' => function ($one, $two, $three, $four, $five) {
            },
        ];

        foreach ($middlewares as $type => $middleware) {
            $dataSet = 'errorless-' . $type;
            yield $dataSet => [$middleware, null];


            foreach ($this->throwablesProvider() as $errorType => $args) {
                $dataSet = $errorType . '-' . $type;
                $throwable = array_shift($args);

                yield $dataSet => [$middleware, $throwable];
            }
        }
    }

    /**
     * @dataProvider nonStandardMiddlewareProvider
     * @group error-handling
     */
    public function testThrowsThrowablesRaisedByNextMiddlewareWhenRaiseThrowablesFlagIsEnabled($middleware, $throwable)
    {
        $route = new Route('/', $middleware);
        $next = $this->prophesize(Next::class);
        $next
            ->__invoke(
                Argument::type(ServerRequestInterface::class),
                Argument::type(ResponseInterface::class),
                $throwable
            )
            ->will(function () use ($throwable) {
                if (null === $throwable) {
                    return $throwable;
                }
                throw $throwable;
            });

        $dispatch = new Dispatch();
        $dispatch->raiseThrowables();

        if (null === $throwable) {
            $this->assertNull($dispatch(
                $route,
                $throwable,
                $this->request->reveal(),
                $this->response->reveal(),
                $next->reveal()
            ));
            return;
        }

        try {
            $dispatch(
                $route,
                $throwable,
                $this->request->reveal(),
                $this->response->reveal(),
                $next->reveal()
            );
            $this->fail('Dispatch succeeded and should not have');
        } catch (\Throwable $e) {
            $this->assertSame($throwable, $e, sprintf(
                'Throwable raised is not the one expected: %s',
                $e->getMessage()
            ));
        } catch (\Exception $e) {
            $this->assertSame($throwable, $e, sprintf(
                'Exception raised is not the one expected: %s',
                $e->getMessage()
            ));
        }
    }

    /**
     * @dataProvider throwablesProvider
     * @group error-handling
     */
    public function testThrowsThrowablesRaisedByInteropMiddlewareWhenRaiseThrowablesFlagIsEnabled($throwable)
    {
        $middleware = $this->prophesize(ServerMiddlewareInterface::class);
        $middleware
            ->process(
                Argument::type(ServerRequestInterface::class),
                Argument::type(DelegateInterface::class)
            )
            // Necessary to do this as willThrow does not support Throwable
            // types yet:
            ->will(function () use ($throwable) {
                throw $throwable;
            });

        $route = new Route('/', $middleware->reveal());
        $next = $this->prophesize(Next::class);
        $next
            ->__invoke(
                Argument::type(ServerRequestInterface::class),
                Argument::type(ResponseInterface::class),
                $throwable
            )
            ->shouldNotBeCalled();

        $dispatch = new Dispatch();
        $dispatch->raiseThrowables();

        try {
            $dispatch->process($route, $this->request->reveal(), $next->reveal());
            $this->fail('Dispatch succeeded and should not have');
        } catch (\Throwable $e) {
            $this->assertSame($throwable, $e, sprintf(
                'Throwable raised is not the one expected: %s',
                $e->getMessage()
            ));
        } catch (\Exception $e) {
            $this->assertSame($throwable, $e, sprintf(
                'Exception raised is not the one expected: %s',
                $e->getMessage()
            ));
        }
    }
}
