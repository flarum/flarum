<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Api\Serializer;

use Flarum\Core\Notification;
use InvalidArgumentException;

class NotificationSerializer extends AbstractSerializer
{
    /**
     * {@inheritdoc}
     */
    protected $type = 'notifications';

    /**
     * A map of notification types (key) to the serializer that should be used
     * to output the notification's subject (value).
     *
     * @var array
     */
    protected static $subjectSerializers = [];

    /**
     * {@inheritdoc}
     *
     * @param \Flarum\Core\Notification $notification
     * @throws InvalidArgumentException
     */
    protected function getDefaultAttributes($notification)
    {
        if (! ($notification instanceof Notification)) {
            throw new InvalidArgumentException(
                get_class($this).' can only serialize instances of '.Notification::class
            );
        }

        return [
            'id'          => (int) $notification->id,
            'contentType' => $notification->type,
            'content'     => $notification->data,
            'time'        => $this->formatDate($notification->time),
            'isRead'      => (bool) $notification->is_read
        ];
    }

    /**
     * @return \Tobscure\JsonApi\Relationship
     */
    protected function user($notification)
    {
        return $this->hasOne($notification, 'Flarum\Api\Serializer\UserBasicSerializer');
    }

    /**
     * @return \Tobscure\JsonApi\Relationship
     */
    protected function sender($notification)
    {
        return $this->hasOne($notification, 'Flarum\Api\Serializer\UserBasicSerializer');
    }

    /**
     * @return \Tobscure\JsonApi\Relationship
     */
    protected function subject($notification)
    {
        return $this->hasOne($notification, function ($notification) {
            return static::$subjectSerializers[$notification->type];
        });
    }

    /**
     * @param $type
     * @param $serializer
     */
    public static function setSubjectSerializer($type, $serializer)
    {
        static::$subjectSerializers[$type] = $serializer;
    }
}
