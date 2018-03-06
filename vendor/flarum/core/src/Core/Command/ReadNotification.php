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

class ReadNotification
{
    /**
     * The ID of the notification to mark as read.
     *
     * @var int
     */
    public $notificationId;

    /**
     * The user performing the action.
     *
     * @var User
     */
    public $actor;

    /**
     * @param int $notificationId The ID of the notification to mark as read.
     * @param User $actor The user performing the action.
     */
    public function __construct($notificationId, User $actor)
    {
        $this->notificationId = $notificationId;
        $this->actor = $actor;
    }
}
