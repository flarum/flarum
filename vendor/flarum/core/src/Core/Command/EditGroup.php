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

class EditGroup
{
    /**
     * The ID of the group to edit.
     *
     * @var int
     */
    public $groupId;

    /**
     * The user performing the action.
     *
     * @var User
     */
    public $actor;

    /**
     * The attributes to update on the post.
     *
     * @var array
     */
    public $data;

    /**
     * @param int $groupId The ID of the group to edit.
     * @param User $actor The user performing the action.
     * @param array $data The attributes to update on the post.
     */
    public function __construct($groupId, User $actor, array $data)
    {
        $this->groupId = $groupId;
        $this->actor = $actor;
        $this->data = $data;
    }
}
