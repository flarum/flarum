<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Core\Repository;

use Flarum\Core\Discussion;
use Flarum\Core\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Expression;

class DiscussionRepository
{
    /**
     * Get a new query builder for the discussions table.
     *
     * @return Builder
     */
    public function query()
    {
        return Discussion::query();
    }

    /**
     * Find a discussion by ID, optionally making sure it is visible to a
     * certain user, or throw an exception.
     *
     * @param int $id
     * @param User $user
     * @return \Flarum\Core\Discussion
     */
    public function findOrFail($id, User $user = null)
    {
        $query = Discussion::where('id', $id);

        return $this->scopeVisibleTo($query, $user)->firstOrFail();
    }

    /**
     * Get the IDs of discussions which a user has read completely.
     *
     * @param User $user
     * @return array
     */
    public function getReadIds(User $user)
    {
        return Discussion::leftJoin('users_discussions', 'users_discussions.discussion_id', '=', 'discussions.id')
            ->where('user_id', $user->id)
            ->where('read_number', '>=', new Expression('last_post_number'))
            ->lists('id')
            ->all();
    }

    /**
     * Scope a query to only include records that are visible to a user.
     *
     * @param Builder $query
     * @param User $user
     * @return Builder
     */
    protected function scopeVisibleTo(Builder $query, User $user = null)
    {
        if ($user !== null) {
            $query->whereVisibleTo($user);
        }

        return $query;
    }
}
