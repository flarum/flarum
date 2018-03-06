<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Tags\Api\Serializer;

use Flarum\Api\Serializer\AbstractSerializer;
use Flarum\Api\Serializer\DiscussionSerializer;

class TagSerializer extends AbstractSerializer
{
    /**
     * {@inheritdoc}
     */
    protected $type = 'tags';

    /**
     * {@inheritdoc}
     */
    protected function getDefaultAttributes($tag)
    {
        $attributes = [
            'name'               => $tag->name,
            'description'        => $tag->description,
            'slug'               => $tag->slug,
            'color'              => $tag->color,
            'backgroundUrl'      => $tag->background_path,
            'backgroundMode'     => $tag->background_mode,
            'iconUrl'            => $tag->icon_path,
            'discussionsCount'   => (int) $tag->discussions_count,
            'position'           => $tag->position === null ? null : (int) $tag->position,
            'defaultSort'        => $tag->default_sort,
            'isChild'            => (bool) $tag->parent_id,
            'isHidden'           => (bool) $tag->is_hidden,
            'lastTime'           => $this->formatDate($tag->last_time),
            'canStartDiscussion' => $this->actor->can('startDiscussion', $tag),
            'canAddToDiscussion' => $this->actor->can('addToDiscussion', $tag)
        ];

        if ($this->actor->isAdmin()) {
            $attributes['isRestricted'] = (bool) $tag->is_restricted;
        }

        return $attributes;
    }

    /**
     * @return \Tobscure\JsonApi\Relationship
     */
    protected function parent($tag)
    {
        return $this->hasOne($tag, self::class);
    }

    /**
     * @return \Tobscure\JsonApi\Relationship
     */
    protected function lastDiscussion($tag)
    {
        return $this->hasOne($tag, DiscussionSerializer::class);
    }
}
