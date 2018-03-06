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

class DeleteAvatar
{
    /**
     * The ID of the user to delete the avatar of.
     *
     * @var int
     */
    public $userId;

    /**
     * The user performing the action.
     *
     * @var User
     */
    public $actor;

    /**
     * @param int $userId The ID of the user to delete the avatar of.
     * @param User $actor The user performing the action.
     */
    public function __construct($userId, User $actor)
    {
        $this->userId = $userId;
        $this->actor = $actor;
    }
}
