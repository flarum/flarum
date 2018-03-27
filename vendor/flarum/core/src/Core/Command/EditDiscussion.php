<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Core\Command;

use Flarum\Core\User;

class EditDiscussion
{
    /**
     * The ID of the discussion to edit.
     *
     * @var int
     */
    public $discussionId;

    /**
     * The user performing the action.
     *
     * @var \Flarum\Core\User
     */
    public $actor;

    /**
     * The attributes to update on the discussion.
     *
     * @var array
     */
    public $data;

    /**
     * @param int $discussionId The ID of the discussion to edit.
     * @param \Flarum\Core\User $actor The user performing the action.
     * @param array $data The attributes to update on the discussion.
     */
    public function __construct($discussionId, User $actor, array $data)
    {
        $this->discussionId = $discussionId;
        $this->actor = $actor;
        $this->data = $data;
    }
}
