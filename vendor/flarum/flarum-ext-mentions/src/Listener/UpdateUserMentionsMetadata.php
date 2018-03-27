<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Mentions\Listener;

use Flarum\Api\Serializer\PostBasicSerializer;
use Flarum\Core\Notification\NotificationSyncer;
use Flarum\Core\Post;
use Flarum\Core\User;
use Flarum\Event\ConfigureNotificationTypes;
use Flarum\Event\PostWasDeleted;
use Flarum\Event\PostWasHidden;
use Flarum\Event\PostWasPosted;
use Flarum\Event\PostWasRestored;
use Flarum\Event\PostWasRevised;
use Flarum\Mentions\Notification\UserMentionedBlueprint;
use Illuminate\Contracts\Events\Dispatcher;
use s9e\TextFormatter\Utils;

class UpdateUserMentionsMetadata
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
        $events->listen(PostWasPosted::class, [$this, 'whenPostWasPosted']);
        $events->listen(PostWasRevised::class, [$this, 'whenPostWasRevised']);
        $events->listen(PostWasHidden::class, [$this, 'whenPostWasHidden']);
        $events->listen(PostWasRestored::class, [$this, 'whenPostWasRestored']);
        $events->listen(PostWasDeleted::class, [$this, 'whenPostWasDeleted']);
    }

    /**
     * @param ConfigureNotificationTypes $event
     */
    public function addNotificationType(ConfigureNotificationTypes $event)
    {
        $event->add(UserMentionedBlueprint::class, PostBasicSerializer::class, ['alert']);
    }

    /**
     * @param PostWasPosted $event
     */
    public function whenPostWasPosted(PostWasPosted $event)
    {
        $this->postBecameVisible($event->post);
    }

    /**
     * @param PostWasRevised $event
     */
    public function whenPostWasRevised(PostWasRevised $event)
    {
        $this->postBecameVisible($event->post);
    }

    /**
     * @param PostWasHidden $event
     */
    public function whenPostWasHidden(PostWasHidden $event)
    {
        $this->postBecameInvisible($event->post);
    }

    /**
     * @param PostWasRestored $event
     */
    public function whenPostWasRestored(PostWasRestored $event)
    {
        $this->postBecameVisible($event->post);
    }

    /**
     * @param PostWasDeleted $event
     */
    public function whenPostWasDeleted(PostWasDeleted $event)
    {
        $this->postBecameInvisible($event->post);
    }

    /**
     * @param Post $post
     */
    protected function postBecameVisible(Post $post)
    {
        $mentioned = Utils::getAttributeValues($post->parsedContent, 'USERMENTION', 'id');

        $this->sync($post, $mentioned);
    }

    /**
     * @param Post $post
     */
    protected function postBecameInvisible(Post $post)
    {
        $this->sync($post, []);
    }

    /**
     * @param Post $post
     * @param array $mentioned
     */
    protected function sync(Post $post, array $mentioned)
    {
        $post->mentionsUsers()->sync($mentioned);

        $users = User::whereIn('id', $mentioned)
            ->get()
            ->filter(function ($user) use ($post) {
                return $post->isVisibleTo($user) && $user->id !== $post->user->id;
            })
            ->all();

        $this->notifications->sync(new UserMentionedBlueprint($post), $users);
    }
}
