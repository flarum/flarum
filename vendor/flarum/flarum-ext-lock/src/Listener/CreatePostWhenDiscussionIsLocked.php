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

use Flarum\Api\Serializer\DiscussionBasicSerializer;
use Flarum\Core\Discussion;
use Flarum\Core\Notification\NotificationSyncer;
use Flarum\Core\User;
use Flarum\Event\ConfigureNotificationTypes;
use Flarum\Event\ConfigurePostTypes;
use Flarum\Lock\Event\DiscussionWasLocked;
use Flarum\Lock\Event\DiscussionWasUnlocked;
use Flarum\Lock\Notification\DiscussionLockedBlueprint;
use Flarum\Lock\Post\DiscussionLockedPost;
use Illuminate\Contracts\Events\Dispatcher;

class CreatePostWhenDiscussionIsLocked
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
        $events->listen(ConfigurePostTypes::class, [$this, 'addPostType']);
        $events->listen(ConfigureNotificationTypes::class, [$this, 'addNotificationType']);
        $events->listen(DiscussionWasLocked::class, [$this, 'whenDiscussionWasLocked']);
        $events->listen(DiscussionWasUnlocked::class, [$this, 'whenDiscussionWasUnlocked']);
    }

    /**
     * @param ConfigurePostTypes $event
     */
    public function addPostType(ConfigurePostTypes $event)
    {
        $event->add(DiscussionLockedPost::class);
    }

    /**
     * @param ConfigureNotificationTypes $event
     */
    public function addNotificationType(ConfigureNotificationTypes $event)
    {
        $event->add(DiscussionLockedBlueprint::class, DiscussionBasicSerializer::class, ['alert']);
    }

    /**
     * @param DiscussionWasLocked $event
     */
    public function whenDiscussionWasLocked(DiscussionWasLocked $event)
    {
        $this->lockedChanged($event->discussion, $event->user, true);
    }

    /**
     * @param DiscussionWasUnlocked $event
     */
    public function whenDiscussionWasUnlocked(DiscussionWasUnlocked $event)
    {
        $this->lockedChanged($event->discussion, $event->user, false);
    }

    /**
     * @param Discussion $discussion
     * @param User $user
     * @param $isLocked
     */
    protected function lockedChanged(Discussion $discussion, User $user, $isLocked)
    {
        $post = DiscussionLockedPost::reply(
            $discussion->id,
            $user->id,
            $isLocked
        );

        $post = $discussion->mergePost($post);

        if ($discussion->start_user_id !== $user->id) {
            $notification = new DiscussionLockedBlueprint($post);

            $this->notifications->sync($notification, $post->exists ? [$discussion->startUser] : []);
        }
    }
}
