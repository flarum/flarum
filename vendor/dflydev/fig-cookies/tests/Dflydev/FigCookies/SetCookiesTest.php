<?php

namespace Dflydev\FigCookies;

class SetCookiesTest extends \PHPUnit_Framework_TestCase
{
    const INTERFACE_PSR_HTTP_MESSAGE_RESPONSE = 'Psr\Http\Message\ResponseInterface';

    /**
     * @param string[] $setCookieStrings
     * @param SetCookie[] $expectedSetCookies
     *
     * @test
     * @dataProvider provideSetCookieStringsAndExpectedSetCookiesData
     */
    public function it_creates_from_response($setCookieStrings, array $expectedSetCookies)
    {
        $response = $this->prophesize(static::INTERFACE_PSR_HTTP_MESSAGE_RESPONSE);
        $response->getHeader(SetCookies::SET_COOKIE_HEADER)->willReturn($setCookieStrings);

        $setCookies = SetCookies::fromResponse($response->reveal());

        $this->assertEquals($expectedSetCookies, $setCookies->getAll());
    }

    /**
     * @param string[] $setCookieStrings
     * @param SetCookie[] $expectedSetCookies
     *
     * @test
     * @dataProvider provideSetCookieStringsAndExpectedSetCookiesData
     */
    public function it_creates_from_set_cookie_strings($setCookieStrings, array $expectedSetCookies)
    {
        $setCookies = SetCookies::fromSetCookieStrings($setCookieStrings);

        $this->assertEquals($expectedSetCookies, $setCookies->getAll());
    }

    /**
     * @param string[] $setCookieStrings
     * @param SetCookie[] $expectedSetCookies
     *
     * @test
     * @dataProvider provideSetCookieStringsAndExpectedSetCookiesData
     */
    public function it_knows_which_set_cookies_are_available($setCookieStrings, array $expectedSetCookies)
    {
        $setCookies = SetCookies::fromSetCookieStrings($setCookieStrings);

        foreach ($expectedSetCookies as $expectedSetCookie) {
            $this->assertTrue($setCookies->has($expectedSetCookie->getName()));
        }

        $this->assertFalse($setCookies->has('i know this cookie does not exist'));
    }

    /**
     * @test
     * @dataProvider provideGetsSetCookieByNameData
     */
    public function it_gets_set_cookie_by_name($setCookieStrings, $setCookieName, SetCookie $expectedSetCookie = null)
    {
        $setCookies = SetCookies::fromSetCookieStrings($setCookieStrings);

        $this->assertEquals($expectedSetCookie, $setCookies->get($setCookieName));
    }

    /**
     * @test
     */
    public function it_renders_added_and_removed_set_cookies_header()
    {
        $setCookies = SetCookies::fromSetCookieStrings(['theme=light', 'sessionToken=abc123', 'hello=world'])
            ->with(SetCookie::create('theme', 'blue'))
            ->without('sessionToken')
            ->with(SetCookie::create('who', 'me'))
        ;

        $originalResponse = new FigCookieTestingResponse();
        $response = $setCookies->renderIntoSetCookieHeader($originalResponse);

        $this->assertNotEquals($response, $originalResponse);

        $this->assertEquals(
            ['theme=blue', 'hello=world', 'who=me'],
            $response->getHeader(SetCookies::SET_COOKIE_HEADER)
        );
    }

    /**
     * @test
     */
    public function it_gets_and_updates_set_cookie_value_on_request()
    {
        //
        // Example of naive cookie encryption middleware.
        //
        // Shows how to access and manipulate cookies using PSR-7 Response
        // instances from outside the Response object itself.
        //

        // Simulate a response coming in with several cookies.
        $response = (new FigCookieTestingResponse())
            ->withAddedHeader(SetCookies::SET_COOKIE_HEADER, 'theme=light')
            ->withAddedHeader(SetCookies::SET_COOKIE_HEADER, 'sessionToken=ENCRYPTED')
            ->withAddedHeader(SetCookies::SET_COOKIE_HEADER, 'hello=world')
        ;

        // Get our set cookies from the response.
        $setCookies = SetCookies::fromResponse($response);

        // Ask for the encrypted session token.
        $decryptedSessionToken = $setCookies->get('sessionToken');

        // Get the encrypted value from the cookie and decrypt it.
        $decryptedValue = $decryptedSessionToken->getValue();
        $encryptedValue = str_rot13($decryptedValue);

        // Create a new set cookie with the encrypted value.
        $encryptedSessionToken = $decryptedSessionToken->withValue($encryptedValue);

        // Include our encrypted session token with the rest of our cookies.
        $setCookies = $setCookies->with($encryptedSessionToken);

        // Render our cookies, along with the newly decrypted session token, into a response.
        $response = $setCookies->renderIntoSetCookieHeader($response);

        // From this point on, any response based on this one can get the encrypted version
        // of the session token.
        $this->assertEquals(
            ['theme=light', 'sessionToken=RAPELCGRQ', 'hello=world'],
            $response->getHeader(SetCookies::SET_COOKIE_HEADER)
        );
    }

    public function provideSetCookieStringsAndExpectedSetCookiesData()
    {
        return [
            [
                [],
                [],
            ],
            [
                [
                    'someCookie=',
                ],
                [
                    SetCookie::create('someCookie'),
                ],
            ],
            [
                [
                    'someCookie=someValue',
                    'LSID=DQAAAK%2FEaem_vYg; Path=/accounts; Expires=Wed, 13 Jan 2021 22:23:01 GMT; Secure; HttpOnly',
                ],
                [
                    SetCookie::create('someCookie', 'someValue'),
                    SetCookie::create('LSID')
                        ->withValue('DQAAAK/Eaem_vYg')
                        ->withPath('/accounts')
                        ->withExpires('Wed, 13 Jan 2021 22:23:01 GMT')
                        ->withSecure(true)
                        ->withHttpOnly(true),
                ],
            ],
            [
                [
                    'a=AAA',
                    'b=BBB',
                    'c=CCC',
                ],
                [
                    SetCookie::create('a', 'AAA'),
                    SetCookie::create('b', 'BBB'),
                    SetCookie::create('c', 'CCC'),
                ],
            ],
        ];
    }

    public function provideGetsSetCookieByNameData()
    {
        return [
            [
                [
                    'a=AAA',
                    'b=BBB',
                    'c=CCC',
                ],
                'b',
                SetCookie::create('b', 'BBB'),
            ],
            [
                [
                    'a=AAA',
                    'b=BBB',
                    'c=CCC',
                    'LSID=DQAAAK%2FEaem_vYg; Path=/accounts; Expires=Wed, 13 Jan 2021 22:23:01 GMT; Secure; HttpOnly',
                ],
                'LSID',
                SetCookie::create('LSID')
                    ->withValue('DQAAAK/Eaem_vYg')
                    ->withPath('/accounts')
                    ->withExpires('Wed, 13 Jan 2021 22:23:01 GMT')
                    ->withSecure(true)
                    ->withHttpOnly(true),
            ],
            [
                [
                    'a=AAA',
                    'b=BBB',
                    'c=CCC',
                ],
                'LSID',
                null,
            ],
        ];
    }
}
