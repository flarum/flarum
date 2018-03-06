<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @see       http://github.com/zendframework/zend-stratigility for the canonical source repository
 * @copyright Copyright (c) 2015-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-stratigility/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Stratigility;

use Exception;
use PHPUnit_Framework_TestCase as TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Zend\Diactoros\ServerRequest as PsrRequest;
use Zend\Diactoros\Response as PsrResponse;
use Zend\Diactoros\Uri;
use Zend\Escaper\Escaper;
use Zend\Stratigility\FinalHandler;
use Zend\Stratigility\Http\Request;
use Zend\Stratigility\Http\Response;

class FinalHandlerTest extends TestCase
{
    public $errorHandler;

    /**
     * @var Escaper
     */
    private $escaper;

    /**
     * @var FinalHandler
     */
    private $final;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var Response
     */
    private $response;

    public function setUp()
    {
        $this->restoreErrorHandler();
        $this->errorHandler = function ($errno, $errstr) {
            if (false !== strstr($errstr, Request::class . ' is now deprecated')) {
                return true;
            }
            if (false !== strstr($errstr, Response::class . ' is now deprecated')) {
                return true;
            }

            return false;
        };
        set_error_handler($this->errorHandler, E_USER_DEPRECATED);

        $psrRequest     = new PsrRequest([], [], 'http://example.com/', 'GET', 'php://memory');
        $this->escaper  = new Escaper();
        $this->request  = new Request($psrRequest);
        $this->response = new Response(new PsrResponse());
        $this->final    = new FinalHandler();
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

    public function testInvokingWithErrorAndNoStatusCodeSetsStatusTo500()
    {
        $error    = 'error';
        $response = call_user_func($this->final, $this->request, $this->response, $error);
        $this->assertEquals(500, $response->getStatusCode());
    }

    public function testInvokingWithExceptionWithValidCodeSetsStatusToExceptionCode()
    {
        $error    = new Exception('foo', 400);
        $response = call_user_func($this->final, $this->request, $this->response, $error);
        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testInvokingWithExceptionWithInvalidCodeSetsStatusTo500()
    {
        $error    = new Exception('foo', 32001);
        $response = call_user_func($this->final, $this->request, $this->response, $error);
        $this->assertEquals(500, $response->getStatusCode());
    }

    public function testInvokingWithErrorInNonProductionModeSetsResponseBodyToError()
    {
        $error    = 'error';
        $this->final = new FinalHandler(['env' => 'not-production']);
        $response = call_user_func($this->final, $this->request, $this->response, $error);
        $this->assertEquals($error, (string) $response->getBody());
    }

    public function testInvokingWithExceptionInNonProductionModeIncludesExceptionMessageInResponseBody()
    {
        $error    = new Exception('foo', 400);
        $this->final = new FinalHandler(['env' => 'not-production']);
        $response = call_user_func($this->final, $this->request, $this->response, $error);
        $expected = $this->escaper->escapeHtml($error->getMessage());
        $this->assertContains($expected, (string) $response->getBody());
    }

    public function testInvokingWithExceptionInNonProductionModeIncludesTraceInResponseBody()
    {
        $error    = new Exception('foo', 400);
        $this->final = new FinalHandler(['env' => 'not-production']);
        $response = call_user_func($this->final, $this->request, $this->response, $error);
        $expected = $this->escaper->escapeHtml($error->getTraceAsString());
        $this->assertContains($expected, (string) $response->getBody());
    }

    public function testInvokingWithExceptionInNonProductionModeIncludesPrevTraceInResponseBody()
    {
        $prev     = new \Exception('boobar', 500);
        $error    = new Exception('foo', 400, $prev);
        $final    = new FinalHandler(['env' => 'development'], $this->response);
        $response = call_user_func($final, $this->request, $this->response, $error);
        $expected = $this->escaper->escapeHtml($error->getTraceAsString());
        $body = (string) $response->getBody();
        $this->assertContains($expected, $body);
        $this->assertContains('boobar', $body);
        $this->assertContains('foo', $body);
    }

    public function testInvokingWithErrorAndNoEnvironmentModeSetDoesNotSetResponseBodyToError()
    {
        $error    = 'error';
        $response = call_user_func($this->final, $this->request, $this->response, $error);
        $this->assertNotEquals($error, (string) $response->getBody());
    }

    public function testInvokingWithExceptionAndNoEnvironmentModeSetDoesNotIncludeExceptionMessageInResponseBody()
    {
        $error    = new Exception('foo', 400);
        $response = call_user_func($this->final, $this->request, $this->response, $error);
        $expected = $this->escaper->escapeHtml($error->getMessage());
        $this->assertNotContains($expected, (string) $response->getBody());
    }

    public function testInvokingWithExceptionAndNoEnvironmentModeSetDoesNotIncludeTraceInResponseBody()
    {
        $error    = new Exception('foo', 400);
        $response = call_user_func($this->final, $this->request, $this->response, $error);
        $expected = $this->escaper->escapeHtml($error->getTraceAsString());
        $this->assertNotContains($expected, (string) $response->getBody());
    }

    public function testInvokingWithErrorInProductionSetsResponseToReasonPhrase()
    {
        $final = new FinalHandler([
            'env' => 'production',
        ]);
        $error    = new Exception('foo', 400);
        $response = $final($this->request, $this->response, $error);
        $this->assertEquals($response->getReasonPhrase(), (string) $response->getBody());
    }

    public function testTriggersOnErrorCallableWithErrorWhenPresent()
    {
        $error     = (object) ['error' => true];
        $triggered = null;
        $callback  = function ($error, $request, $response) use (&$triggered) {
            $triggered = func_get_args();
        };

        $final = new FinalHandler([
            'env' => 'production',
            'onerror' => $callback,
        ]);
        $response = $final($this->request, $this->response, $error);
        $this->assertInternalType('array', $triggered);
        $this->assertEquals(3, count($triggered));
        $this->assertSame($error, array_shift($triggered));
        $this->assertSame($this->request, array_shift($triggered));
        $this->assertSame($response, array_shift($triggered));
    }

    public function testCreates404ResponseWhenNoErrorIsPresent()
    {
        $response = call_user_func($this->final, $this->request, $this->response, null);
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testErrorResponsePreservesResponseReasonPhraseIfStatusCodeMatchesExceptionCode()
    {
        $this->response = $this->response->withStatus(500, 'It broke!');
        $response = call_user_func($this->final, $this->request, $this->response, new \Exception('foo', 500));
        $this->assertSame($this->response->getReasonPhrase(), $response->getReasonPhrase());
    }

    public function testErrorResponseUsesStandardHttpStatusCodeReasonPhraseIfExceptionCodeCausesStatusCodeToChange()
    {
        $response = call_user_func($this->final, $this->request, $this->response, new \Exception('foo', 418));
        $this->assertSame("I'm a teapot", $response->getReasonPhrase());
    }

    public function test404ResponseIncludesOriginalRequestUri()
    {
        $originalUrl = 'http://local.example.com/bar/foo';
        $psrRequest  = new PsrRequest([], [], $originalUrl, 'GET', 'php://memory');
        $request     = new Request($psrRequest);
        $request     = $request->withUri(new Uri('http://local.example.com/foo'));

        $final    = new FinalHandler();
        $response = call_user_func($final, $request, $this->response, null);
        $this->assertContains($originalUrl, (string) $response->getBody());
    }

    /**
     * @group 12
     */
    public function testReturnsResponseIfItDoesNotMatchResponsePassedToConstructor()
    {
        $psrResponse = new PsrResponse();
        $originalResponse = new Response($psrResponse);
        $final = new FinalHandler([], $originalResponse);

        $passedResponse = new Response($psrResponse);
        $result = $final(new Request(new PsrRequest()), $passedResponse);
        $this->assertSame($passedResponse, $result);
    }

    /**
     * @group 12
     */
    public function testReturnsResponseIfBodyLengthHasChanged()
    {
        $psrResponse = new PsrResponse();
        $response    = new Response($psrResponse);
        $final       = new FinalHandler([], $response);

        $response->write('return this response');

        $result = $final(new Request(new PsrRequest()), $response);
        $this->assertSame($response, $result);
    }

    public function testCanReplaceOriginalResponseAndBodySizeAfterConstruction()
    {
        $psrResponse = new PsrResponse();
        $originalResponse = new Response(new PsrResponse());
        $originalResponse->write('foo');

        $final = new FinalHandler([], $psrResponse);
        $final->setOriginalResponse($originalResponse);

        /** @var Response $actualResponse */
        $actualResponse = self::readAttribute($final, 'response');
        $this->assertSame($originalResponse, $actualResponse);
        $this->assertSame(3, $actualResponse->getBody()->getSize());
    }

    /**
     * @group 53
     */
    public function testShouldNotMarkStratigilityResponseAsCompleteWhenHandlingErrors()
    {
        $error = new Exception('Exception message', 501);

        $body = $this->prophesize(StreamInterface::class);
        $body->write('Not Implemented')->shouldBeCalled();

        $response = $this->prophesize('Zend\Stratigility\Http\Response');
        $response->getStatusCode()->willReturn(200);
        $response->withStatus(501, '')->will(function () use ($response) {
            return $response->reveal();
        });
        $response->getReasonPhrase()->willReturn('Not Implemented');
        $response->getBody()->will([$body, 'reveal']);

        $final = new FinalHandler([], new Response(new PsrResponse()));
        $this->assertSame($response->reveal(), $final(
            $this->prophesize('Zend\Stratigility\Http\Request')->reveal(),
            $response->reveal(),
            $error
        ));
    }

    /**
     * @group 53
     */
    public function testShouldNotDecoratePsrResponseAsStratigilityCompletedResponseWhenHandlingErrors()
    {
        $error = new Exception('Exception message', 501);

        $response = (new PsrResponse())
            ->withStatus(200);

        $final = new FinalHandler([], new Response(new PsrResponse()));
        $test = $final(
            $this->prophesize('Zend\Stratigility\Http\Request')->reveal(),
            $response,
            $error
        );

        $this->assertInstanceOf(ResponseInterface::class, $test);
        $this->assertSame(501, $test->getStatusCode());
        $this->assertSame('Not Implemented', $test->getReasonPhrase());

        $body = $test->getBody();
        $body->rewind();
        $this->assertContains('Not Implemented', $body->getContents());
    }

    /**
     * @group 53
     */
    public function testShouldNotMarkStratigilityResponseAsCompleteWhenCreating404s()
    {
        $body     = $this->prophesize('Psr\Http\Message\StreamInterface');
        $body->getSize()->willReturn(0)->shouldBeCalledTimes(2);
        $body->write("Cannot GET /foo\n")->shouldBeCalled();

        $response = $this->prophesize('Zend\Stratigility\Http\Response');
        $response->getBody()->will(function () use ($body) {
            return $body->reveal();
        });
        $response->withStatus(404)->will(function () use ($response) {
            return $response->reveal();
        });
        $response->getBody()->will([$body, 'reveal']);

        $request = $this->prophesize('Zend\Diactoros\ServerRequest');
        $request->getAttribute('originalRequest', false)->willReturn(false);
        $request->getUri()->willReturn('/foo');
        $request->getMethod()->willReturn('GET');

        $final = new FinalHandler([], $response->reveal());
        $this->assertSame($response->reveal(), $final(
            $request->reveal(),
            $response->reveal()
        ));
    }

    /**
     * @group 53
     */
    public function testShouldNotDecoratePsrResponseAsStratigilityCompletedResponseWhenCreating404s()
    {
        $response = new PsrResponse();

        $request = $this->prophesize('Zend\Diactoros\ServerRequest');
        $request->getAttribute('originalRequest', false)->willReturn(false);
        $request->getUri()->willReturn('/foo');
        $request->getMethod()->willReturn('GET');

        $final = new FinalHandler([], $response);
        $test = $final(
            $request->reveal(),
            $response
        );
        $this->assertInstanceOf(ResponseInterface::class, $test);
        $this->assertSame(404, $test->getStatusCode());

        $body = $test->getBody();
        $body->rewind();
        $this->assertContains('Cannot GET /foo', $body->getContents());
    }
}
