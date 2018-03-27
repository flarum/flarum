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

use Flarum\Core\Discussion;
use InvalidArgumentException;

class DiscussionBasicSerializer extends AbstractSerializer
{
    /**
     * {@inheritdoc}
     */
    protected $type = 'discussions';

    /**
     * {@inheritdoc}
     *
     * @param Discussion $discussion
     * @throws InvalidArgumentException
     */
    protected function getDefaultAttributes($discussion)
    {
        if (! ($discussion instanceof Discussion)) {
            throw new InvalidArgumentException(
                get_class($this).' can only serialize instances of '.Discussion::class
            );
        }

        return [
            'title' => $discussion->title,
            'slug'  => $discussion->slug,
        ];
    }

    /**
     * @return \Tobscure\JsonApi\Relationship
     */
    protected function startUser($discussion)
    {
        return $this->hasOne($discussion, 'Flarum\Api\Serializer\UserBasicSerializer');
    }

    /**
     * @return \Tobscure\JsonApi\Relationship
     */
    protected function startPost($discussion)
    {
        return $this->hasOne($discussion, 'Flarum\Api\Serializer\PostBasicSerializer');
    }

    /**
     * @return \Tobscure\JsonApi\Relationship
     */
    protected function lastUser($discussion)
    {
        return $this->hasOne($discussion, 'Flarum\Api\Serializer\UserBasicSerializer');
    }

    /**
     * @return \Tobscure\JsonApi\Relationship
     */
    protected function lastPost($discussion)
    {
        return $this->hasOne($discussion, 'Flarum\Api\Serializer\PostBasicSerializer');
    }

    /**
     * @return \Tobscure\JsonApi\Relationship
     */
    protected function posts($discussion)
    {
        return $this->hasMany($discussion, 'Flarum\Api\Serializer\PostSerializer');
    }

    /**
     * @return \Tobscure\JsonApi\Relationship
     */
    protected function relevantPosts($discussion)
    {
        return $this->hasMany($discussion, 'Flarum\Api\Serializer\PostBasicSerializer');
    }
}
