<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Lock\Access;

use Flarum\Core\Access\AbstractPolicy;
use Flarum\Core\Discussion;
use Flarum\Core\User;

class DiscussionPolicy extends AbstractPolicy
{
    /**
     * {@inheritdoc}
     */
    protected $model = Discussion::class;

    /**
     * @param User $actor
     * @param Discussion $discussion
     * @return bool
     */
    public function reply(User $actor, Discussion $discussion)
    {
        if ($discussion->is_locked && $actor->cannot('lock', $discussion)) {
            return false;
        }
    }
}
