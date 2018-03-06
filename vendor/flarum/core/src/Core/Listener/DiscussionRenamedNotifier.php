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

use Flarum\Core\Notification\DiscussionRenamedBlueprint;
use Flarum\Core\Notification\NotificationSyncer;
use Flarum\Core\Post\DiscussionRenamedPost;
use Flarum\Event\DiscussionWasRenamed;
use Illuminate\Contracts\Events\Dispatcher;

class DiscussionRenamedNotifier
{
    /**
     * @var NotificationSyncer
     */
    protected $notifications;

    /**
     * @param NotificationSyncer $notifications
     */
    public function __construct(NotificationSyncer $notifications)
    {
        $this->notifications = $notifications;
    }

    /**
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events)
    {
        $events->listen(DiscussionWasRenamed::class, [$this, 'whenDiscussionWasRenamed']);
    }

    /**
     * @param \Flarum\Event\DiscussionWasRenamed $event
     */
    public function whenDiscussionWasRenamed(DiscussionWasRenamed $event)
    {
        $post = DiscussionRenamedPost::reply(
            $event->discussion->id,
            $event->actor->id,
            $event->oldTitle,
            $event->discussion->title
        );

        $post = $event->discussion->mergePost($post);

        if ($event->discussion->start_user_id !== $event->actor->id) {
            $blueprint = new DiscussionRenamedBlueprint($post);

            if ($post->exists) {
                $this->notifications->sync($blueprint, [$event->discussion->startUser]);
            } else {
                $this->notifications->delete($blueprint);
            }
        }
    }
}
