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
use Flarum\Flags\Flag;
use Flarum\Tags\Tag;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Expression;

class FlagPolicy extends AbstractPolicy
{
    /**
     * {@inheritdoc}
     */
    protected $model = Flag::class;

    /**
     * @param User $actor
     * @param Builder $query
     */
    public function find(User $actor, Builder $query)
    {
        $query
            ->select('flags.*')
            ->leftJoin('posts', 'posts.id', '=', 'flags.post_id')
            ->leftJoin('discussions', 'discussions.id', '=', 'posts.discussion_id')
            ->whereNotExists(function ($query) use ($actor) {
                return $query->select(new Expression(1))
                    ->from('discussions_tags')
                    ->whereIn('tag_id', Tag::getIdsWhereCannot($actor, 'discussion.viewFlags'))
                    ->where('discussions.id', new Expression('discussion_id'));
            });
    }
}
