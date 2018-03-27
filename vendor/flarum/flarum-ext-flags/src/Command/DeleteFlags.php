<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Flags\Command;

use Flarum\Core\User;

class DeleteFlags
{
    /**
     * The ID of the post to delete flags for.
     *
     * @var int
     */
    public $postId;

    /**
     * The user performing the action.
     *
     * @var User
     */
    public $actor;

    /**
     * @var array
     */
    public $data;

    /**
     * @param int $postId The ID of the post to delete flags for.
     * @param User $actor The user performing the action.
     * @param array $data
     */
    public function __construct($postId, User $actor, array $data = [])
    {
        $this->postId = $postId;
        $this->actor = $actor;
        $this->data = $data;
    }
}
