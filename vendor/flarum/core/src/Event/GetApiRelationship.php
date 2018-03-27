<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Event;

use Flarum\Api\Serializer\AbstractSerializer;

/**
 * Get an API serializer relationship.
 *
 * This event is fired when a relationship is to be included on an API document.
 * If a handler wishes to fulfil the given relationship, then it should return
 * an instance of `Tobscure\JsonApi\Relationship`.
 *
 * @see AbstractSerializer::hasOne()
 * @see AbstractSerializer::hasMany()
 * @see https://github.com/tobscure/json-api
 */
class GetApiRelationship
{
    /**
     * @var AbstractSerializer
     */
    public $serializer;

    /**
     * @var string
     */
    public $relationship;

    /**
     * @var mixed
     */
    public $model;

    /**
     * @param AbstractSerializer $serializer
     * @param string $relationship
     * @param mixed $model
     */
    public function __construct(AbstractSerializer $serializer, $relationship, $model)
    {
        $this->serializer = $serializer;
        $this->relationship = $relationship;
        $this->model = $model;
    }

    /**
     * @param string $serializer
     * @param string $relationship
     * @return bool
     */
    public function isRelationship($serializer, $relationship)
    {
        return $this->serializer instanceof $serializer && $this->relationship === $relationship;
    }
}
