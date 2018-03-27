<?php

/*
 * This file is part of JSON-API.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tobscure\Tests\JsonApi\Element;

use Tobscure\JsonApi\AbstractSerializer;
use Tobscure\JsonApi\Collection;
use Tobscure\JsonApi\Resource;
use Tobscure\Tests\JsonApi\AbstractTestCase;

/**
 * This is the collection test class.
 *
 * @author Toby Zerner <toby.zerner@gmail.com>
 */
class CollectionTest extends AbstractTestCase
{
    public function testToArrayReturnsArrayOfResources()
    {
        $serializer = new PostSerializer3;

        $post1 = (object) ['id' => 1, 'foo' => 'bar'];
        $post2 = new Resource((object) ['id' => 2, 'foo' => 'baz'], $serializer);

        $collection = new Collection([$post1, $post2], $serializer);

        $resource1 = new Resource($post1, $serializer);
        $resource2 = $post2;

        $this->assertEquals([$resource1->toArray(), $resource2->toArray()], $collection->toArray());
    }

    public function testToIdentifierReturnsArrayOfResourceIdentifiers()
    {
        $serializer = new PostSerializer3;

        $post1 = (object) ['id' => 1];
        $post2 = (object) ['id' => 2];

        $collection = new Collection([$post1, $post2], $serializer);

        $resource1 = new Resource($post1, $serializer);
        $resource2 = new Resource($post2, $serializer);

        $this->assertEquals([$resource1->toIdentifier(), $resource2->toIdentifier()], $collection->toIdentifier());
    }
}

class PostSerializer3 extends AbstractSerializer
{
    protected $type = 'posts';

    public function getAttributes($post, array $fields = null)
    {
        return ['foo' => $post->foo];
    }
}
