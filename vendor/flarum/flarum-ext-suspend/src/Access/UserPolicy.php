<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Suspend\Access;

use Flarum\Core\Access\AbstractPolicy;
use Flarum\Core\User;

class UserPolicy extends AbstractPolicy
{
    /**
     * {@inheritdoc}
     */
    protected $model = User::class;

    /**
     * @param User $actor
     * @param User $user
     * @return bool|null
     */
    public function suspend(User $actor, User $user)
    {
        if ($user->isAdmin() || $user->id === $actor->id) {
            return false;
        }
    }
}
