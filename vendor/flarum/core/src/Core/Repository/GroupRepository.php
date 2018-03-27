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

use Flarum\Core\Group;
use Flarum\Core\User;
use Illuminate\Database\Eloquent\Builder;

class GroupRepository
{
    /**
     * Get a new query builder for the groups table.
     *
     * @return Builder
     */
    public function query()
    {
        return User::query();
    }

    /**
     * Find a user by ID, optionally making sure it is visible to a certain
     * user, or throw an exception.
     *
     * @param int $id
     * @param User $actor
     * @return Group
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail($id, User $actor = null)
    {
        $query = Group::where('id', $id);

        return $this->scopeVisibleTo($query, $actor)->firstOrFail();
    }

    /**
     * Find a group by name.
     *
     * @param string $name
     * @return User|null
     */
    public function findByName($name, User $actor = null)
    {
        $query = Group::where('name_singular', $name)->orWhere('name_plural', $name);

        return $this->scopeVisibleTo($query, $actor)->first();
    }

    /**
     * Scope a query to only include records that are visible to a user.
     *
     * @param Builder $query
     * @param User $actor
     * @return Builder
     */
    protected function scopeVisibleTo(Builder $query, User $actor = null)
    {
        if ($actor !== null) {
            $query->whereVisibleTo($actor);
        }

        return $query;
    }
}
