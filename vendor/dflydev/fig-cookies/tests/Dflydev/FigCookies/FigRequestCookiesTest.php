<?php

namespace Dflydev\FigCookies;

class FigRequestCookiesTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function it_gets_cookies()
    {
        $request = (new FigCookieTestingRequest())
            ->withHeader(Cookies::COOKIE_HEADER, 'theme=light; sessionToken=RAPELCGRQ; hello=world')
        ;

        $this->assertEquals(
            'RAPELCGRQ',
            FigRequestCookies::get($request, 'sessionToken')->getValue()
        );
    }

    /**
     * @test
     */
    public function it_sets_cookies()
    {
        $request = (new FigCookieTestingRequest())
            ->withHeader(Cookies::COOKIE_HEADER, 'theme=light; sessionToken=RAPELCGRQ; hello=world')
        ;

        $request = FigRequestCookies::set($request, Cookie::create('hello', 'WORLD!'));

        $this->assertEquals(
            'theme=light; sessionToken=RAPELCGRQ; hello=WORLD%21',
            $request->getHeaderLine('Cookie')
        );
    }

    /**
     * @test
     */
    public function it_modifies_cookies()
    {
        $request = (new FigCookieTestingRequest())
            ->withHeader(Cookies::COOKIE_HEADER, 'theme=light; sessionToken=RAPELCGRQ; hello=world')
        ;

        $request = FigRequestCookies::modify($request, 'hello', function (Cookie $cookie) {
            return $cookie->withValue(strtoupper($cookie->getName()));
        });

        $this->assertEquals(
            'theme=light; sessionToken=RAPELCGRQ; hello=HELLO',
            $request->getHeaderLine('Cookie')
        );
    }

    /**
     * @test
     */
    public function it_removes_cookies()
    {
        $request = (new FigCookieTestingRequest())
            ->withHeader(Cookies::COOKIE_HEADER, 'theme=light; sessionToken=RAPELCGRQ; hello=world')
        ;

        $request = FigRequestCookies::remove($request, 'sessionToken');

        $this->assertEquals(
            'theme=light; hello=world',
            $request->getHeaderLine('Cookie')
        );
    }
}
