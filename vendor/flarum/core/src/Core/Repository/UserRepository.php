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

use Flarum\Core\User;
use Illuminate\Database\Eloquent\Builder;

class UserRepository
{
    /**
     * Get a new query builder for the users table.
     *
     * @return \Illuminate\Database\Eloquent\Builder
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
     * @return User
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail($id, User $actor = null)
    {
        $query = User::where('id', $id);

        return $this->scopeVisibleTo($query, $actor)->firstOrFail();
    }

    /**
     * Find a user by an identification (username or email).
     *
     * @param string $identification
     * @return User|null
     */
    public function findByIdentification($identification)
    {
        $field = filter_var($identification, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        return User::where($field, $identification)->first();
    }

    /**
     * Find a user by email.
     *
     * @param string $email
     * @return User|null
     */
    public function findByEmail($email)
    {
        return User::where('email', $email)->first();
    }

    /**
     * Get the ID of a user with the given username.
     *
     * @param string $username
     * @param User|null $actor
     * @return int|null
     */
    public function getIdForUsername($username, User $actor = null)
    {
        $query = User::where('username', 'like', $username);

        return $this->scopeVisibleTo($query, $actor)->value('id');
    }

    /**
     * Find users by matching a string of words against their username,
     * optionally making sure they are visible to a certain user.
     *
     * @param string $string
     * @param User|null $actor
     * @return array
     */
    public function getIdsForUsername($string, User $actor = null)
    {
        $query = User::where('username', 'like', '%'.$string.'%')
            ->orderByRaw('username = ? desc', [$string])
            ->orderByRaw('username like ? desc', [$string.'%']);

        return $this->scopeVisibleTo($query, $actor)->lists('id');
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
