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

use Flarum\Core\Discussion;
use Flarum\Core\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * The `ScopePrivatePostVisibility` event.
 */
class ScopePrivatePostVisibility
{
    /**
     * @var Discussion
     */
    public $discussion;

    /**
     * @var Builder
     */
    public $query;

    /**
     * @var User
     */
    public $actor;

    /**
     * @param Discussion $discussion
     * @param Builder $query
     * @param User $actor
     */
    public function __construct(Discussion $discussion, Builder $query, User $actor)
    {
        $this->discussion = $discussion;
        $this->query = $query;
        $this->actor = $actor;
    }
}
