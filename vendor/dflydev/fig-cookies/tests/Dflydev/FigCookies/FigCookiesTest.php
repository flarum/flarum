<?php

namespace Dflydev\FigCookies;

class FigCookiesTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function it_encrypts_and_decrypts_cookies()
    {
        // Simulate a request coming in with several cookies.
        $request = (new FigCookieTestingRequest())
            ->withHeader(Cookies::COOKIE_HEADER, 'theme=light; sessionToken=RAPELCGRQ; hello=world')
        ;

        // "Before" Middleware Example
        //
        // Get our token from an encrypted cookie value, "decrypt" it, and replace the cookie on the request.
        // From here on out, any part of the system that gets our token will be able to see the contents
        // in plaintext.
        $request = FigRequestCookies::modify($request, 'sessionToken', function (Cookie $cookie) {
            return $cookie->withValue(str_rot13($cookie->getValue()));
        });

        // Even though the sessionToken initially comes in "encrypted", at this point (and any point in
        // the future) the sessionToken cookie will be available in plaintext.
        $this->assertEquals(
            'theme=light; sessionToken=ENCRYPTED; hello=world',
            $request->getHeaderLine(Cookies::COOKIE_HEADER)
        );

        // Simulate a response going out.
        $response = (new FigCookieTestingResponse());

        // Various parts of the system will add set cookies to the response. In this case, we are
        // going to show that the rest of the system interacts with the session token using
        // plaintext.
        $response = $response
            ->withAddedHeader(SetCookies::SET_COOKIE_HEADER, SetCookie::create('theme', 'light'))
            ->withAddedHeader(SetCookies::SET_COOKIE_HEADER, SetCookie::create('sessionToken', 'ENCRYPTED'))
            ->withAddedHeader(SetCookies::SET_COOKIE_HEADER, SetCookie::create('hello', 'world'))
        ;

        // "After" Middleware Example
        //
        // Get our token from an unencrypted set cookie value, "encrypt" it, and replace the cook on the response.
        // From here on out, any part of the system that gets our token will only be able to see the encrypted
        // value.
        $response = FigResponseCookies::modify($response, 'sessionToken', function (SetCookie $setCookie) {
            return $setCookie->withValue(str_rot13($setCookie->getValue()));
        });

        // Even though the sessionToken intiially went out "decrypted", at this point (and at any point
        // in the future) the sessionToken cookie will remain "encrypted."
        $this->assertEquals(
            ['theme=light', 'sessionToken=RAPELCGRQ', 'hello=world'],
            $response->getHeader(SetCookies::SET_COOKIE_HEADER)
        );
    }
}
