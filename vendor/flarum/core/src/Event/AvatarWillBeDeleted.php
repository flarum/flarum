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

class AvatarWillBeDeleted
{
    /**
     * The user whose avatar will be deleted.
     *
     * @var User
     */
    public $user;

    /**
     * The user performing the action.
     *
     * @var User
     */
    public $actor;

    /**
     * @param User $user The user whose avatar will be deleted.
     * @param User $actor The user performing the action.
     */
    public function __construct(User $user, User $actor)
    {
        $this->user = $user;
        $this->actor = $actor;
    }
}
