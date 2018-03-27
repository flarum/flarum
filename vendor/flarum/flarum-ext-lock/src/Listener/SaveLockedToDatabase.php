<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Lock\Listener;

use Flarum\Core\Access\AssertPermissionTrait;
use Flarum\Event\DiscussionWillBeSaved;
use Flarum\Lock\Event\DiscussionWasLocked;
use Flarum\Lock\Event\DiscussionWasUnlocked;
use Illuminate\Contracts\Events\Dispatcher;

class SaveLockedToDatabase
{
    use AssertPermissionTrait;

    /**
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events)
    {
        $events->listen(DiscussionWillBeSaved::class, [$this, 'whenDiscussionWillBeSaved']);
    }

    /**
     * @param DiscussionWillBeSaved $event
     */
    public function whenDiscussionWillBeSaved(DiscussionWillBeSaved $event)
    {
        if (isset($event->data['attributes']['isLocked'])) {
            $isLocked = (bool) $event->data['attributes']['isLocked'];
            $discussion = $event->discussion;
            $actor = $event->actor;

            $this->assertCan($actor, 'lock', $discussion);

            if ((bool) $discussion->is_locked === $isLocked) {
                return;
            }

            $discussion->is_locked = $isLocked;

            $discussion->raise(
                $discussion->is_locked
                    ? new DiscussionWasLocked($discussion, $actor)
                    : new DiscussionWasUnlocked($discussion, $actor)
            );
        }
    }
}
