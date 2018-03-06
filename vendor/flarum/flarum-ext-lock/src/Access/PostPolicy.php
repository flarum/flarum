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
use Flarum\Core\Post;
use Flarum\Core\User;

class PostPolicy extends AbstractPolicy
{
    /**
     * {@inheritdoc}
     */
    protected $model = Post::class;

    /**
     * @param User $actor
     * @param Post $post
     * @return bool
     */
    public function edit(User $actor, Post $post)
    {
        $discussion = $post->discussion;
        if ($discussion->is_locked && $actor->cannot('lock', $discussion)) {
            return false;
        }
    }
}
