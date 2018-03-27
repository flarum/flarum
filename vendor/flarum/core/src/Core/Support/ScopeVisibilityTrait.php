<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Core\Support;

use Flarum\Core\User;
use Flarum\Event\ScopeModelVisibility;
use Illuminate\Database\Eloquent\Builder;

trait ScopeVisibilityTrait
{
    /**
     * Scope a query to only include records that are visible to a user.
     *
     * @param Builder $query
     * @param User $actor
     */
    public function scopeWhereVisibleTo(Builder $query, User $actor)
    {
        static::$dispatcher->fire(
            new ScopeModelVisibility($query->getModel(), $query, $actor)
        );
    }
}
