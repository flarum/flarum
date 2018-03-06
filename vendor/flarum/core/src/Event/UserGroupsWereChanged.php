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

class UserGroupsWereChanged
{
    /**
     * The user whose groups were changed.
     *
     * @var User
     */
    public $user;

    /**
     * @var \Flarum\Core\Group[]
     */
    public $oldGroups;

    /**
     * @var User
     */
    public $actor;

    /**
     * @param User $user The user whose groups were changed.
     * @param \Flarum\Core\Group[] $oldGroups
     * @param User $actor
     */
    public function __construct(User $user, array $oldGroups, User $actor = null)
    {
        $this->user = $user;
        $this->oldGroups = $oldGroups;
        $this->actor = $actor;
    }
}
