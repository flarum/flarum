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

class DeleteUser
{
    /**
     * The ID of the user to delete.
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
     * Any other user input associated with the action. This is unused by
     * default, but may be used by extensions.
     *
     * @var array
     */
    public $data;

    /**
     * @param int $userId The ID of the user to delete.
     * @param User $actor The user performing the action.
     * @param array $data Any other user input associated with the action. This
     *     is unused by default, but may be used by extensions.
     */
    public function __construct($userId, User $actor, array $data = [])
    {
        $this->userId = $userId;
        $this->actor = $actor;
        $this->data = $data;
    }
}
