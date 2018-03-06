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

use Tobscure\JsonApi\AbstractSerializer;
use Tobscure\JsonApi\Document;
use Tobscure\JsonApi\Resource;

/**
 * This is the document test class.
 *
 * @author Toby Zerner <toby.zerner@gmail.com>
 */
class DocumentTest extends AbstractTestCase
{
    public function testToArrayIncludesTheResourcesRepresentation()
    {
        $post = (object) [
            'id' => 1,
            'foo' => 'bar'
        ];

        $resource = new Resource($post, new PostSerializer2);

        $document = new Document($resource);

        $this->assertEquals(['data' => $resource->toArray()], $document->toArray());
    }

    public function testItCanBeSerializedToJson()
    {
        $this->assertEquals('[]', (string) new Document());
    }
}

class PostSerializer2 extends AbstractSerializer
{
    protected $type = 'posts';

    public function getAttributes($post, array $fields = null)
    {
        return ['foo' => $post->foo];
    }
}
