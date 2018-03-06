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
use Flarum\Event\ConfigureNotificationTypes;
use Flarum\Event\PostWasDeleted;
use Flarum\Event\PostWasHidden;
use Flarum\Event\PostWasPosted;
use Flarum\Event\PostWasRestored;
use Flarum\Event\PostWasRevised;
use Flarum\Mentions\Notification\PostMentionedBlueprint;
use Illuminate\Contracts\Events\Dispatcher;
use s9e\TextFormatter\Utils;

class UpdatePostMentionsMetadata
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
        $event->add(PostMentionedBlueprint::class, PostBasicSerializer::class, ['alert']);
    }

    /**
     * @param PostWasPosted $event
     */
    public function whenPostWasPosted(PostWasPosted $event)
    {
        $this->replyBecameVisible($event->post);
    }

    /**
     * @param PostWasRevised $event
     */
    public function whenPostWasRevised(PostWasRevised $event)
    {
        $this->replyBecameVisible($event->post);
    }

    /**
     * @param PostWasHidden $event
     */
    public function whenPostWasHidden(PostWasHidden $event)
    {
        $this->replyBecameInvisible($event->post);
    }

    /**
     * @param PostWasRestored $event
     */
    public function whenPostWasRestored(PostWasRestored $event)
    {
        $this->replyBecameVisible($event->post);
    }

    /**
     * @param PostWasDeleted $event
     */
    public function whenPostWasDeleted(PostWasDeleted $event)
    {
        $this->replyBecameInvisible($event->post);
    }

    /**
     * @param Post $reply
     */
    protected function replyBecameVisible(Post $reply)
    {
        $mentioned = Utils::getAttributeValues($reply->parsedContent, 'POSTMENTION', 'id');

        $this->sync($reply, $mentioned);
    }

    /**
     * @param Post $reply
     */
    protected function replyBecameInvisible(Post $reply)
    {
        $this->sync($reply, []);
    }

    /**
     * @param Post $reply
     * @param array $mentioned
     */
    protected function sync(Post $reply, array $mentioned)
    {
        $reply->mentionsPosts()->sync($mentioned);

        $posts = Post::with('user')
            ->whereIn('id', $mentioned)
            ->get()
            ->filter(function ($post) use ($reply) {
                return $post->user && $post->user->id !== $reply->user_id;
            })
            ->all();

        foreach ($posts as $post) {
            $this->notifications->sync(new PostMentionedBlueprint($post, $reply), [$post->user]);
        }
    }
}
