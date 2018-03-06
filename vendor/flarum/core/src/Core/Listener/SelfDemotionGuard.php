<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Core\Listener;

use Flarum\Core\Exception\PermissionDeniedException;
use Flarum\Core\Group;
use Flarum\Event\UserWillBeSaved;
use Illuminate\Contracts\Events\Dispatcher;

class SelfDemotionGuard
{
    /**
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events)
    {
        $events->listen(UserWillBeSaved::class, [$this, 'whenUserWillBeSaved']);
    }

    /**
     * Prevent an admin from removing their admin permission via the API.
     * @param UserWillBeSaved $event
     * @throws PermissionDeniedException
     */
    public function whenUserWillBeSaved(UserWillBeSaved $event)
    {
        // Non-admin users pose no problem
        if (! $event->actor->isAdmin()) {
            return;
        }

        // Only admins can demote users, which means demoting other users is
        // fine, because we still have at least one admin (the actor) left
        if ($event->actor->id !== $event->user->id) {
            return;
        }

        $groups = array_get($event->data, 'relationships.groups.data');

        // If there is no group data (not even an empty array), this means
        // groups were not changed (and thus not removed) - we're fine!
        if (! isset($groups)) {
            return;
        }

        $adminGroups = array_filter($groups, function ($group) {
            return $group['id'] == Group::ADMINISTRATOR_ID;
        });

        // As long as the user is still part of the admin group, all is good
        if ($adminGroups) {
            return;
        }

        // If we get to this point, we have to prohibit the edit
        throw new PermissionDeniedException;
    }
}
