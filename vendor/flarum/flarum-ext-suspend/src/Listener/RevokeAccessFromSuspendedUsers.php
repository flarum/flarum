<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Suspend\Listener;

use Carbon\Carbon;
use Flarum\Core\Group;
use Flarum\Event\PrepareUserGroups;
use Illuminate\Contracts\Events\Dispatcher;

class RevokeAccessFromSuspendedUsers
{
    /**
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events)
    {
        $events->listen(PrepareUserGroups::class, [$this, 'prepareUserGroups']);
    }

    /**
     * @param PrepareUserGroups $event
     */
    public function prepareUserGroups(PrepareUserGroups $event)
    {
        $suspendUntil = $event->user->suspend_until;

        if ($suspendUntil && $suspendUntil->gt(Carbon::now())) {
            $event->groupIds = [Group::GUEST_ID];
        }
    }
}
