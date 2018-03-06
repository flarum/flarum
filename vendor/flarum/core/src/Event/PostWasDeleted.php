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

use Flarum\Core\Post;
use Flarum\Core\User;

class PostWasDeleted
{
    /**
     * @var \Flarum\Core\Post
     */
    public $post;

    /**
     * @var User
     */
    public $actor;

    /**
     * @param \Flarum\Core\Post $post
     */
    public function __construct(Post $post, User $actor = null)
    {
        $this->post = $post;
        $this->actor = $actor;
    }
}
