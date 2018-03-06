<?php

namespace Dflydev\FigCookies;

class CookieTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     * @dataProvider provideParsesOneFromCookieStringData
     */
    public function it_parses_one_from_cookie_string($cookieString, $expectedName, $expectedValue)
    {
        $cookie = Cookie::oneFromCookiePair($cookieString);

        $this->assertCookieNameAndValue($cookie, $expectedName, $expectedValue);
    }

    /**
     * @test
     * @dataProvider provideParsesListFromCookieString
     */
    public function it_parses_list_from_cookie_string($cookieString, array $expectedNameValuePairs)
    {
        $cookies = Cookie::listFromCookieString($cookieString);

        $this->assertCount(count($expectedNameValuePairs), $cookies);

        for ($i = 0; $i < count($cookies); $i++) {
            $cookie = $cookies[$i];
            list ($expectedName, $expectedValue) = $expectedNameValuePairs[$i];

            $this->assertCookieNameAndValue($cookie, $expectedName, $expectedValue);
        }
    }

    private function assertCookieNameAndValue(Cookie $cookie, $expectedName, $expectedValue)
    {
        $this->assertEquals($expectedName, $cookie->getName());
        $this->assertEquals($expectedValue, $cookie->getValue());
    }

    public function provideParsesOneFromCookieStringData()
    {
        return [
            ['someCookie=something', 'someCookie', 'something'],
            ['hello%3Dworld=how%22are%27you', 'hello=world', 'how"are\'you'],
            ['empty=', 'empty', ''],
        ];
    }

    public function provideParsesListFromCookieString()
    {
        return [
            ['theme=light; sessionToken=abc123', [
                ['theme', 'light'],
                ['sessionToken', 'abc123'],
            ]],

            ['theme=light; sessionToken=abc123;', [
                ['theme', 'light'],
                ['sessionToken', 'abc123'],
            ]],
        ];
    }
}
