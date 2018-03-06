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

use Flarum\Core\Post;
use Flarum\Core\Post\CommentPost;
use InvalidArgumentException;

class PostBasicSerializer extends AbstractSerializer
{
    /**
     * {@inheritdoc}
     */
    protected $type = 'posts';

    /**
     * {@inheritdoc}
     *
     * @param \Flarum\Core\Post $post
     * @throws InvalidArgumentException
     */
    protected function getDefaultAttributes($post)
    {
        if (! ($post instanceof Post)) {
            throw new InvalidArgumentException(
                get_class($this).' can only serialize instances of '.Post::class
            );
        }

        $attributes = [
            'id'          => (int) $post->id,
            'number'      => (int) $post->number,
            'time'        => $this->formatDate($post->time),
            'contentType' => $post->type
        ];

        if ($post instanceof CommentPost) {
            $attributes['contentHtml'] = $post->content_html;
        } else {
            $attributes['content'] = $post->content;
        }

        return $attributes;
    }

    /**
     * @return \Tobscure\JsonApi\Relationship
     */
    protected function user($post)
    {
        return $this->hasOne($post, 'Flarum\Api\Serializer\UserBasicSerializer');
    }

    /**
     * @return \Tobscure\JsonApi\Relationship
     */
    protected function discussion($post)
    {
        return $this->hasOne($post, 'Flarum\Api\Serializer\DiscussionBasicSerializer');
    }
}
