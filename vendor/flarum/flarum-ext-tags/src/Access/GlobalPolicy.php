<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Tags\Access;

use Flarum\Event\GetPermission;
use Flarum\Tags\Tag;
use Illuminate\Contracts\Events\Dispatcher;

class GlobalPolicy
{
    /**
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events)
    {
        $events->listen(GetPermission::class, [$this, 'grantGlobalDiscussionPermissions']);
    }

    /**
     * @param GetPermission $event
     * @return bool
     */
    public function grantGlobalDiscussionPermissions(GetPermission $event)
    {
        if (in_array($event->ability, ['viewDiscussions', 'startDiscussion']) && is_null($event->model)) {
            return ! empty(Tag::getIdsWhereCan($event->actor, $event->ability));
        }
    }
}
