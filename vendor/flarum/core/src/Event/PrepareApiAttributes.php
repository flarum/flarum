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

use DateTime;
use Flarum\Api\Serializer\AbstractSerializer;

/**
 * Prepare API attributes.
 *
 * This event is fired when a serialize is constructing an array of resource
 * attributes for API output.
 */
class PrepareApiAttributes
{
    /**
     * The class doing the serializing.
     *
     * @var AbstractSerializer
     */
    public $serializer;

    /**
     * The model being serialized.
     *
     * @var object
     */
    public $model;

    /**
     * The serialized attributes of the resource.
     *
     * @var array
     */
    public $attributes;

    /**
     * @var \Flarum\Core\User
     */
    public $actor;

    /**
     * @param AbstractSerializer $serializer The class doing the serializing.
     * @param object|array $model The model being serialized.
     * @param array $attributes The serialized attributes of the resource.
     */
    public function __construct(AbstractSerializer $serializer, $model, array &$attributes)
    {
        $this->serializer = $serializer;
        $this->model = $model;
        $this->attributes = &$attributes;
        $this->actor = $serializer->getActor();
    }

    /**
     * @param string $serializer
     * @return bool
     */
    public function isSerializer($serializer)
    {
        return $this->serializer instanceof $serializer;
    }

    /**
     * @param DateTime|null $date
     * @return string|null
     */
    public function formatDate(DateTime $date = null)
    {
        if ($date) {
            return $date->format(DateTime::RFC3339);
        }
    }
}
