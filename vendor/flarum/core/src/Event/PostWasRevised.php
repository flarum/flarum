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

use Flarum\Core\Post\CommentPost;
use Flarum\Core\User;

class PostWasRevised
{
    /**
     * @var CommentPost
     */
    public $post;

    /**
     * @var User
     */
    public $actor;

    /**
     * @param CommentPost $post
     */
    public function __construct(CommentPost $post, User $actor = null)
    {
        $this->post = $post;
        $this->actor = $actor;
    }
}
