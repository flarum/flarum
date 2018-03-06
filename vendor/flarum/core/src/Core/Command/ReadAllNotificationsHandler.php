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

use Flarum\Core\Access\AssertPermissionTrait;
use Flarum\Core\Repository\NotificationRepository;

class ReadAllNotificationsHandler
{
    use AssertPermissionTrait;

    /**
     * @var NotificationRepository
     */
    protected $notifications;

    /**
     * @param NotificationRepository $notifications
     */
    public function __construct(NotificationRepository $notifications)
    {
        $this->notifications = $notifications;
    }

    /**
     * @param ReadAllNotifications $command
     * @throws \Flarum\Core\Exception\PermissionDeniedException
     */
    public function handle(ReadAllNotifications $command)
    {
        $actor = $command->actor;

        $this->assertRegistered($actor);

        $this->notifications->markAllAsRead($actor);
    }
}
