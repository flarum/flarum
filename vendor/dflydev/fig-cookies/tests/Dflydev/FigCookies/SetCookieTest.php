<?php

namespace Dflydev\FigCookies;

class SetCookieTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     * @dataProvider provideParsesFromSetCookieStringData
     */
    public function it_parses_from_set_cookie_string($cookieString, SetCookie $expectedSetCookie)
    {
        $setCookie = SetCookie::fromSetCookieString($cookieString);

        $this->assertEquals($expectedSetCookie, $setCookie);
        $this->assertEquals($cookieString, (string) $setCookie);
    }

    public function provideParsesFromSetCookieStringData()
    {
        return [
            [
                'someCookie=',
                SetCookie::create('someCookie'),
            ],
            [
                'someCookie=someValue',
                SetCookie::create('someCookie')
                    ->withValue('someValue')
            ],
            [
                'LSID=DQAAAK%2FEaem_vYg; Path=/accounts; Expires=Wed, 13 Jan 2021 22:23:01 GMT; Secure; HttpOnly',
                SetCookie::create('LSID')
                    ->withValue('DQAAAK/Eaem_vYg')
                    ->withPath('/accounts')
                    ->withExpires('Wed, 13 Jan 2021 22:23:01 GMT')
                    ->withSecure(true)
                    ->withHttpOnly(true)
            ],
            [
                'HSID=AYQEVn%2F.DKrdst; Domain=.foo.com; Path=/; Expires=Wed, 13 Jan 2021 22:23:01 GMT; HttpOnly',
                SetCookie::create('HSID')
                    ->withValue('AYQEVn/.DKrdst')
                    ->withDomain('.foo.com')
                    ->withPath('/')
                    ->withExpires('Wed, 13 Jan 2021 22:23:01 GMT')
                    ->withHttpOnly(true)
            ],
            [
                'SSID=Ap4P%2F.GTEq; Domain=foo.com; Path=/; Expires=Wed, 13 Jan 2021 22:23:01 GMT; Secure; HttpOnly',
                SetCookie::create('SSID')
                    ->withValue('Ap4P/.GTEq')
                    ->withDomain('foo.com')
                    ->withPath('/')
                    ->withExpires('Wed, 13 Jan 2021 22:23:01 GMT')
                    ->withSecure(true)
                    ->withHttpOnly(true)
            ],
            [
                'lu=Rg3vHJZnehYLjVg7qi3bZjzg; Domain=.example.com; Path=/; Expires=Tue, 15 Jan 2013 21:47:38 GMT; HttpOnly',
                SetCookie::create('lu')
                    ->withValue('Rg3vHJZnehYLjVg7qi3bZjzg')
                    ->withExpires('Tue, 15-Jan-2013 21:47:38 GMT')
                    ->withPath('/')
                    ->withDomain('.example.com')
                    ->withHttpOnly(true)
            ],
            [
                'lu=Rg3vHJZnehYLjVg7qi3bZjzg; Domain=.example.com; Path=/; Max-Age=500; Secure; HttpOnly',
                SetCookie::create('lu')
                    ->withValue('Rg3vHJZnehYLjVg7qi3bZjzg')
                    ->withMaxAge(500)
                    ->withPath('/')
                    ->withDomain('.example.com')
                    ->withSecure(true)
                    ->withHttpOnly(true)
            ],
            [
                'lu=Rg3vHJZnehYLjVg7qi3bZjzg; Domain=.example.com; Path=/; Expires=Tue, 15 Jan 2013 21:47:38 GMT; Max-Age=500; Secure; HttpOnly',
                SetCookie::create('lu')
                    ->withValue('Rg3vHJZnehYLjVg7qi3bZjzg')
                    ->withExpires('Tue, 15-Jan-2013 21:47:38 GMT')
                    ->withMaxAge(500)
                    ->withPath('/')
                    ->withDomain('.example.com')
                    ->withSecure(true)
                    ->withHttpOnly(true)
            ],
            [
                'lu=Rg3vHJZnehYLjVg7qi3bZjzg; Domain=.example.com; Path=/; Expires=Tue, 15 Jan 2013 21:47:38 GMT; Max-Age=500; Secure; HttpOnly',
                SetCookie::create('lu')
                    ->withValue('Rg3vHJZnehYLjVg7qi3bZjzg')
                    ->withExpires(1358286458)
                    ->withMaxAge(500)
                    ->withPath('/')
                    ->withDomain('.example.com')
                    ->withSecure(true)
                    ->withHttpOnly(true)
            ],
            [
                'lu=Rg3vHJZnehYLjVg7qi3bZjzg; Domain=.example.com; Path=/; Expires=Tue, 15 Jan 2013 21:47:38 GMT; Max-Age=500; Secure; HttpOnly',
                SetCookie::create('lu')
                    ->withValue('Rg3vHJZnehYLjVg7qi3bZjzg')
                    ->withExpires(new \DateTime('Tue, 15-Jan-2013 21:47:38 GMT'))
                    ->withMaxAge(500)
                    ->withPath('/')
                    ->withDomain('.example.com')
                    ->withSecure(true)
                    ->withHttpOnly(true)
            ],
        ];
    }

    /**
     * @test
     */
    public function it_expires_cookies()
    {
        $setCookie = SetCookie::createExpired('expire_immediately');

        $this->assertLessThan(time(), $setCookie->getExpires());
    }

    /**
     * @test
     */
    public function it_creates_long_living_cookies()
    {
        $setCookie = SetCookie::createRememberedForever('remember_forever');

        $fourYearsFromNow = (new \DateTime('+4 years'))->getTimestamp();
        $this->assertGreaterThan($fourYearsFromNow, $setCookie->getExpires());
    }
}
