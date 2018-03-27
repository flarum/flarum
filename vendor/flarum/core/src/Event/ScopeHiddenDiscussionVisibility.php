<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Event;

use Flarum\Core\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * The `ScopeHiddenDiscussionVisibility` event.
 */
class ScopeHiddenDiscussionVisibility
{
    /**
     * @var Builder
     */
    public $query;

    /**
     * @var User
     */
    public $actor;

    /**
     * @var string
     */
    public $permission;

    /**
     * @param Builder $query
     * @param User $actor
     * @param string $permission
     */
    public function __construct(Builder $query, User $actor, $permission)
    {
        $this->query = $query;
        $this->actor = $actor;
        $this->permission = $permission;
    }
}
