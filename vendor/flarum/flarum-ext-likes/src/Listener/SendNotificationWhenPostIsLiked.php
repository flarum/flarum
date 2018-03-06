<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Likes\Listener;

use Flarum\Api\Serializer\PostBasicSerializer;
use Flarum\Core\Notification\NotificationSyncer;
use Flarum\Core\Post;
use Flarum\Core\User;
use Flarum\Event\ConfigureNotificationTypes;
use Flarum\Likes\Event\PostWasLiked;
use Flarum\Likes\Event\PostWasUnliked;
use Flarum\Likes\Notification\PostLikedBlueprint;
use Illuminate\Contracts\Events\Dispatcher;

class SendNotificationWhenPostIsLiked
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
        $events->listen(ConfigureNotificationTypes::class, [$this, 'registerNotificationType']);
        $events->listen(PostWasLiked::class, [$this, 'whenPostWasLiked']);
        $events->listen(PostWasUnliked::class, [$this, 'whenPostWasUnliked']);
    }

    /**
     * @param ConfigureNotificationTypes $event
     */
    public function registerNotificationType(ConfigureNotificationTypes $event)
    {
        $event->add(PostLikedBlueprint::class, PostBasicSerializer::class, ['alert']);
    }

    /**
     * @param PostWasLiked $event
     */
    public function whenPostWasLiked(PostWasLiked $event)
    {
        $this->sync($event->post, $event->user, [$event->post->user]);
    }

    /**
     * @param PostWasUnliked $event
     */
    public function whenPostWasUnliked(PostWasUnliked $event)
    {
        $this->sync($event->post, $event->user, []);
    }

    /**
     * @param Post $post
     * @param User $user
     * @param array $recipients
     */
    public function sync(Post $post, User $user, array $recipients)
    {
        if ($post->user->id != $user->id) {
            $this->notifications->sync(
                new PostLikedBlueprint($post, $user),
                $recipients
            );
        }
    }
}
