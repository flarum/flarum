<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Subscriptions\Listener;

use Flarum\Core\Access\AssertPermissionTrait;
use Flarum\Event\DiscussionWillBeSaved;
use Illuminate\Contracts\Events\Dispatcher;

class SaveSubscriptionToDatabase
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
        $discussion = $event->discussion;
        $data = $event->data;

        if (isset($data['attributes']['subscription'])) {
            $actor = $event->actor;
            $subscription = $data['attributes']['subscription'];

            $this->assertRegistered($actor);

            $state = $discussion->stateFor($actor);

            if (! in_array($subscription, ['follow', 'ignore'])) {
                $subscription = null;
            }

            $state->subscription = $subscription;
            $state->save();
        }
    }
}
