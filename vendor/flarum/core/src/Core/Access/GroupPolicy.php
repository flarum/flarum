<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Core\Access;

use Flarum\Core\Group;
use Flarum\Core\User;

class GroupPolicy extends AbstractPolicy
{
    /**
     * {@inheritdoc}
     */
    protected $model = Group::class;

    /**
     * @param User $actor
     * @param string $ability
     * @return bool|null
     */
    public function after(User $actor, $ability)
    {
        if ($actor->hasPermission('group.'.$ability)) {
            return true;
        }
    }
}
