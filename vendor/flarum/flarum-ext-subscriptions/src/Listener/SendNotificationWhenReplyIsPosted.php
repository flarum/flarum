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

use Flarum\Api\Serializer\DiscussionBasicSerializer;
use Flarum\Core\Notification\NotificationSyncer;
use Flarum\Core\Post;
use Flarum\Event\ConfigureNotificationTypes;
use Flarum\Event\PostWasDeleted;
use Flarum\Event\PostWasHidden;
use Flarum\Event\PostWasPosted;
use Flarum\Event\PostWasRestored;
use Flarum\Subscriptions\Notification\NewPostBlueprint;
use Illuminate\Contracts\Events\Dispatcher;

class SendNotificationWhenReplyIsPosted
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
        $events->listen(ConfigureNotificationTypes::class, [$this, 'addNotificationType']);

        // Register with '1' as priority so this runs before discussion metadata
        // is updated, as we need to compare the user's last read number to that
        // of the previous post.
        $events->listen(PostWasPosted::class, [$this, 'whenPostWasPosted'], 1);
        $events->listen(PostWasHidden::class, [$this, 'whenPostWasHidden']);
        $events->listen(PostWasRestored::class, [$this, 'whenPostWasRestored']);
        $events->listen(PostWasDeleted::class, [$this, 'whenPostWasDeleted']);
    }

    /**
     * @param ConfigureNotificationTypes $event
     */
    public function addNotificationType(ConfigureNotificationTypes $event)
    {
        $event->add(NewPostBlueprint::class, DiscussionBasicSerializer::class, ['alert', 'email']);
    }

    /**
     * @param PostWasPosted $event
     */
    public function whenPostWasPosted(PostWasPosted $event)
    {
        $post = $event->post;
        $discussion = $post->discussion;

        $notify = $discussion->readers()
            ->where('users.id', '!=', $post->user_id)
            ->where('users_discussions.subscription', 'follow')
            ->where('users_discussions.read_number', $discussion->last_post_number)
            ->get();

        $this->notifications->sync(
            $this->getNotification($event->post),
            $notify->all()
        );
    }

    /**
     * @param PostWasHidden $event
     */
    public function whenPostWasHidden(PostWasHidden $event)
    {
        $this->notifications->delete($this->getNotification($event->post));
    }

    /**
     * @param PostWasRestored $event
     */
    public function whenPostWasRestored(PostWasRestored $event)
    {
        $this->notifications->restore($this->getNotification($event->post));
    }

    /**
     * @param PostWasDeleted $event
     */
    public function whenPostWasDeleted(PostWasDeleted $event)
    {
        $this->notifications->delete($this->getNotification($event->post));
    }

    /**
     * @param Post $post
     * @return NewPostBlueprint
     */
    protected function getNotification(Post $post)
    {
        return new NewPostBlueprint($post);
    }
}
