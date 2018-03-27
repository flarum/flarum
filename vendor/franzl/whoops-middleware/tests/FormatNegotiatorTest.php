<?php

namespace Franzl\Middleware\Whoops\Test;

use Franzl\Middleware\Whoops\FormatNegotiator;
use Zend\Diactoros\ServerRequest;

class FormatNegotiatorTest extends \PHPUnit_Framework_TestCase
{
    public function test_requests_without_accept_header_returns_html()
    {
        $request = new ServerRequest;
        $format = FormatNegotiator::getPreferredFormat($request);

        $this->assertEquals('html', $format);
    }

    /**
     * @dataProvider knownTypes
     */
    public function test_known_mimetypes_will_return_preferred_format($mimeType, $expectedFormat)
    {
        $format = FormatNegotiator::getPreferredFormat(
            $this->makeRequestWithAccept($mimeType)
        );

        $this->assertEquals($expectedFormat, $format);
    }

    public function knownTypes()
    {
        return [
            ['text/html', 'html'],
            ['application/xhtml+xml', 'html'],
            ['application/json', 'json'],
            ['text/json', 'json'],
            ['application/x-json', 'json'],
            ['text/xml', 'xml'],
            ['application/xml', 'xml'],
            ['application/x-xml', 'xml'],
            ['text/plain', 'txt'],
        ];
    }

    private function makeRequestWithAccept($acceptHeader)
    {
        $request = new ServerRequest;

        return $request->withHeader('accept', $acceptHeader);
    }
}
