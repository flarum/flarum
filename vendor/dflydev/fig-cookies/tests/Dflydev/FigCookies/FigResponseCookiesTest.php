<?php

namespace Dflydev\FigCookies;

class FigResponseCookiesTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function it_gets_cookies()
    {
        $response = (new FigCookieTestingResponse());

        $response = $response
            ->withAddedHeader(SetCookies::SET_COOKIE_HEADER, SetCookie::create('theme', 'light'))
            ->withAddedHeader(SetCookies::SET_COOKIE_HEADER, SetCookie::create('sessionToken', 'ENCRYPTED'))
            ->withAddedHeader(SetCookies::SET_COOKIE_HEADER, SetCookie::create('hello', 'world'))
        ;

        $this->assertEquals(
            'ENCRYPTED',
            FigResponseCookies::get($response, 'sessionToken')->getValue()
        );
    }

    /**
     * @test
     */
    public function it_sets_cookies()
    {
        $response = (new FigCookieTestingResponse());

        $response = $response
            ->withAddedHeader(SetCookies::SET_COOKIE_HEADER, SetCookie::create('theme', 'light'))
            ->withAddedHeader(SetCookies::SET_COOKIE_HEADER, SetCookie::create('sessionToken', 'ENCRYPTED'))
            ->withAddedHeader(SetCookies::SET_COOKIE_HEADER, SetCookie::create('hello', 'world'))
        ;

        $response = FigResponseCookies::set($response, SetCookie::create('hello', 'WORLD!'));

        $this->assertEquals(
            'theme=light,sessionToken=ENCRYPTED,hello=WORLD%21',
            $response->getHeaderLine('Set-Cookie')
        );
    }

    /**
     * @test
     */
    public function it_modifies_cookies()
    {
        $response = (new FigCookieTestingResponse());

        $response = $response
            ->withAddedHeader(SetCookies::SET_COOKIE_HEADER, SetCookie::create('theme', 'light'))
            ->withAddedHeader(SetCookies::SET_COOKIE_HEADER, SetCookie::create('sessionToken', 'ENCRYPTED'))
            ->withAddedHeader(SetCookies::SET_COOKIE_HEADER, SetCookie::create('hello', 'world'))
        ;

        $response = FigResponseCookies::modify($response, 'hello', function (SetCookie $setCookie) {
            return $setCookie->withValue(strtoupper($setCookie->getName()));
        });

        $this->assertEquals(
            'theme=light,sessionToken=ENCRYPTED,hello=HELLO',
            $response->getHeaderLine('Set-Cookie')
        );
    }

    /**
     * @test
     */
    public function it_removes_cookies()
    {
        $response = (new FigCookieTestingResponse());

        $response = $response
            ->withAddedHeader(SetCookies::SET_COOKIE_HEADER, SetCookie::create('theme', 'light'))
            ->withAddedHeader(SetCookies::SET_COOKIE_HEADER, SetCookie::create('sessionToken', 'ENCRYPTED'))
            ->withAddedHeader(SetCookies::SET_COOKIE_HEADER, SetCookie::create('hello', 'world'))
        ;

        $response = FigResponseCookies::remove($response, 'sessionToken');

        $this->assertEquals(
            'theme=light,hello=world',
            $response->getHeaderLine('Set-Cookie')
        );
    }
}
