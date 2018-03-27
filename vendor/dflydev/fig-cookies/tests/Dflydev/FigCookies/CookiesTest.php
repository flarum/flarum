<?php

namespace Dflydev\FigCookies;

class CookiesTest extends \PHPUnit_Framework_TestCase
{
    const INTERFACE_PSR_HTTP_MESSAGE_REQUEST = 'Psr\Http\Message\RequestInterface';

    /**
     * @param string[] $cookieString
     * @param Cookie[] $expectedCookies
     *
     * @test
     * @dataProvider provideCookieStringAndExpectedCookiesData
     */
    public function it_creates_from_request($cookieString, array $expectedCookies)
    {
        $request = $this->prophesize(static::INTERFACE_PSR_HTTP_MESSAGE_REQUEST);
        $request->getHeaderLine(Cookies::COOKIE_HEADER)->willReturn($cookieString);

        $cookies = Cookies::fromRequest($request->reveal());

        $this->assertEquals($expectedCookies, $cookies->getAll());
    }

    /**
     * @param string[] $cookieString
     * @param Cookie[] $expectedCookies
     *
     * @test
     * @dataProvider provideCookieStringAndExpectedCookiesData
     */
    public function it_creates_from_cookie_string($cookieString, array $expectedCookies)
    {
        $cookies = Cookies::fromCookieString($cookieString);

        $this->assertEquals($expectedCookies, $cookies->getAll());
    }

    /**
     * @param string[] $cookieString
     * @param Cookie[] $expectedCookies
     *
     * @test
     * @dataProvider provideCookieStringAndExpectedCookiesData
     */
    public function it_knows_which_cookies_are_available($cookieString, array $expectedCookies)
    {
        $cookies = Cookies::fromCookieString($cookieString);

        foreach ($expectedCookies as $expectedCookie) {
            $this->assertTrue($cookies->has($expectedCookie->getName()));
        }

        $this->assertFalse($cookies->has('i know this cookie does not exist'));
    }

    /**
     * @test
     * @dataProvider provideGetsCookieByNameData
     */
    public function it_gets_cookie_by_name($cookieString, $cookieName, Cookie $expectedCookie)
    {
        $cookies = Cookies::fromCookieString($cookieString);

        $this->assertEquals($expectedCookie, $cookies->get($cookieName));
    }

    /**
     * @test
     */
    public function it_sets_overrides_and_removes_cookie()
    {
        $cookies = new Cookies();

        $cookies = $cookies->with(Cookie::create('theme', 'blue'));

        $this->assertEquals('blue', $cookies->get('theme')->getValue());

        $cookies = $cookies->with(Cookie::create('theme', 'red'));

        $this->assertEquals('red', $cookies->get('theme')->getValue());

        $cookies = $cookies->without('theme');

        $this->assertFalse($cookies->has('theme'));
    }

    /**
     * @test
     */
    public function it_renders_new_cookies_into_empty_cookie_header()
    {
        $cookies = (new Cookies())
            ->with(Cookie::create('theme', 'light'))
            ->with(Cookie::create('sessionToken', 'abc123'))
        ;

        $originalRequest = new FigCookieTestingRequest();
        $request = $cookies->renderIntoCookieHeader($originalRequest);

        $this->assertNotEquals($request, $originalRequest);

        $this->assertEquals('theme=light; sessionToken=abc123', $request->getHeaderLine(Cookies::COOKIE_HEADER));
    }

    /**
     * @test
     */
    public function it_renders_added_and_removed_cookies_header()
    {
        $cookies = Cookies::fromCookieString('theme=light; sessionToken=abc123; hello=world')
            ->with(Cookie::create('theme', 'blue'))
            ->without('sessionToken')
            ->with(Cookie::create('who', 'me'))
        ;

        $originalRequest = new FigCookieTestingRequest();
        $request = $cookies->renderIntoCookieHeader($originalRequest);

        $this->assertNotEquals($request, $originalRequest);

        $this->assertEquals('theme=blue; hello=world; who=me', $request->getHeaderLine(Cookies::COOKIE_HEADER));
    }

    /**
     * @test
     */
    public function it_gets_cookie_value_from_request()
    {
        //
        // Example of accessing a cookie value.
        //

        // Simulate a request coming in with several cookies.
        $request = (new FigCookieTestingRequest())
            ->withHeader(Cookies::COOKIE_HEADER, 'theme=light; sessionToken=RAPELCGRQ; hello=world')
        ;

        $theme = Cookies::fromRequest($request)->get('theme')->getValue();

        $this->assertEquals('light', $theme);
    }

    /**
     * @test
     */
    public function it_gets_and_updates_cookie_value_on_request()
    {
        //
        // Example of naive cookie decryption middleware.
        //
        // Shows how to access and manipulate cookies using PSR-7 Request
        // instances from outside the Request object itself.
        //

        // Simulate a request coming in with several cookies.
        $request = (new FigCookieTestingRequest())
            ->withHeader(Cookies::COOKIE_HEADER, 'theme=light; sessionToken=RAPELCGRQ; hello=world')
        ;

        // Get our cookies from the request.
        $cookies = Cookies::fromRequest($request);

        // Ask for the encrypted session token.
        $encryptedSessionToken = $cookies->get('sessionToken');

        // Get the encrypted value from the cookie and decrypt it.
        $encryptedValue = $encryptedSessionToken->getValue();
        $decryptedValue = str_rot13($encryptedValue);

        // Create a new cookie with the decrypted value.
        $decryptedSessionToken = $encryptedSessionToken->withValue($decryptedValue);

        // Include our decrypted session token with the rest of our cookies.
        $cookies = $cookies->with($decryptedSessionToken);

        // Render our cookies, along with the newly decrypted session token, into a request.
        $request = $cookies->renderIntoCookieHeader($request);

        // From this point on, any request based on this one can get the plaintext version
        // of the session token.
        $this->assertEquals(
            'theme=light; sessionToken=ENCRYPTED; hello=world',
            $request->getHeaderLine(Cookies::COOKIE_HEADER)
        );
    }

    public function provideCookieStringAndExpectedCookiesData()
    {
        return [
            [
                '',
                []
            ],
            [
                'theme=light',
                [
                    Cookie::create('theme', 'light'),
                ]
            ],
            [
                'theme=light; sessionToken=abc123',
                [
                    Cookie::create('theme', 'light'),
                    Cookie::create('sessionToken', 'abc123'),
                ]
            ]
        ];
    }

    public function provideGetsCookieByNameData()
    {
        return [
            ['theme=light', 'theme', Cookie::create('theme', 'light')],
            ['theme=', 'theme', Cookie::create('theme')],
            ['hello=world; theme=light; sessionToken=abc123', 'theme', Cookie::create('theme', 'light')],
            ['hello=world; theme=; sessionToken=abc123', 'theme', Cookie::create('theme')],
        ];
    }
}
