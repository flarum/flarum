<?php

/*
 * This file is part of JSON-API.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tobscure\Tests\JsonApi;

use Tobscure\JsonApi\LinksTrait;

/**
 * This is the document test class.
 *
 * @author Toby Zerner <toby.zerner@gmail.com>
 */
class LinksTraitTest extends AbstractTestCase
{
    public function testAddPaginationLinks()
    {
        $document = new Document;
        $document->addPaginationLinks('http://example.org', [], 0, 20);

        $this->assertEquals([
            'first' => 'http://example.org',
            'next' => 'http://example.org?page%5Boffset%5D=20'
        ], $document->getLinks());

        $document = new Document;
        $document->addPaginationLinks('http://example.org', ['foo' => 'bar', 'page' => ['limit' => 20]], 30, 20, 100);

        $this->assertEquals([
            'first' => 'http://example.org?foo=bar&page%5Blimit%5D=20',
            'prev' => 'http://example.org?foo=bar&page%5Blimit%5D=20&page%5Boffset%5D=10',
            'next' => 'http://example.org?foo=bar&page%5Blimit%5D=20&page%5Boffset%5D=50',
            'last' => 'http://example.org?foo=bar&page%5Blimit%5D=20&page%5Boffset%5D=80'
        ], $document->getLinks());

        $document = new Document;
        $document->addPaginationLinks('http://example.org', ['page' => ['number' => 2]], 50, 20, 100);

        $this->assertEquals([
            'first' => 'http://example.org',
            'prev' => 'http://example.org?page%5Bnumber%5D=2',
            'next' => 'http://example.org?page%5Bnumber%5D=4',
            'last' => 'http://example.org?page%5Bnumber%5D=5'
        ], $document->getLinks());
    }
}

class Document
{
    use LinksTrait;
}
