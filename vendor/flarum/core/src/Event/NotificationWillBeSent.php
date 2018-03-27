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

use Flarum\Core\Notification\BlueprintInterface;

class NotificationWillBeSent
{
    /**
     * The blueprint for the notification.
     *
     * @var BlueprintInterface
     */
    public $blueprint;

    /**
     * The users that the notification will be sent to.
     *
     * @var array
     */
    public $users;

    /**
     * @param BlueprintInterface $blueprint
     * @param \Flarum\Core\User[] $users
     */
    public function __construct(BlueprintInterface $blueprint, array &$users)
    {
        $this->blueprint = $blueprint;
        $this->users = $users;
    }
}
