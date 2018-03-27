<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Tags\Access;

use Flarum\Core\Access\AbstractPolicy;
use Flarum\Core\User;
use Flarum\Tags\Tag;
use Illuminate\Database\Eloquent\Builder;

class TagPolicy extends AbstractPolicy
{
    /**
     * {@inheritdoc}
     */
    protected $model = Tag::class;

    /**
     * @param User $actor
     * @param Builder $query
     */
    public function find(User $actor, Builder $query)
    {
        $query->whereNotIn('id', Tag::getIdsWhereCannot($actor, 'viewDiscussions'));
    }

    /**
     * @param User $actor
     * @param Tag $tag
     * @return bool|null
     */
    public function startDiscussion(User $actor, Tag $tag)
    {
        if ((! $tag->is_restricted && $actor->hasPermission('startDiscussion'))
            || ($tag->is_restricted && $actor->hasPermission('tag'.$tag->id.'.startDiscussion'))) {
            return true;
        }
    }

    /**
     * @param User $actor
     * @param Tag $tag
     * @return bool|null
     */
    public function addToDiscussion(User $actor, Tag $tag)
    {
        return $this->startDiscussion($actor, $tag);
    }
}
