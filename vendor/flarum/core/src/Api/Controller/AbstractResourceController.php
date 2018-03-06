<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Api\Controller;

use Tobscure\JsonApi\Resource;
use Tobscure\JsonApi\SerializerInterface;

abstract class AbstractResourceController extends AbstractSerializeController
{
    /**
     * {@inheritdoc}
     */
    protected function createElement($data, SerializerInterface $serializer)
    {
        return new Resource($data, $serializer);
    }
}
