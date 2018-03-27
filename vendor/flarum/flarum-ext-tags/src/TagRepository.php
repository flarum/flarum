<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Tags;

use Flarum\Core\User;
use Illuminate\Database\Eloquent\Builder;

class TagRepository
{
    /**
     * Get a new query builder for the tags table.
     *
     * @return Builder
     */
    public function query()
    {
        return Tag::query();
    }

    /**
     * Find a tag by ID, optionally making sure it is visible to a certain
     * user, or throw an exception.
     *
     * @param int $id
     * @param User $actor
     * @return Tag
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail($id, User $actor = null)
    {
        $query = Tag::where('id', $id);

        return $this->scopeVisibleTo($query, $actor)->firstOrFail();
    }

    /**
     * Find all tags, optionally making sure they are visible to a
     * certain user.
     *
     * @param User|null $user
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function all(User $user = null)
    {
        $query = Tag::query();

        return $this->scopeVisibleTo($query, $user)->get();
    }

    /**
     * Get the ID of a tag with the given slug.
     *
     * @param string $slug
     * @param User|null $user
     * @return int
     */
    public function getIdForSlug($slug, User $user = null)
    {
        $query = Tag::where('slug', 'like', $slug);

        return $this->scopeVisibleTo($query, $user)->pluck('id');
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
