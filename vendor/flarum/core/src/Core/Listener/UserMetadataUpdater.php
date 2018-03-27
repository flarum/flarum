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

use Flarum\Core\Discussion;
use Flarum\Core\Post;
use Flarum\Event\DiscussionWasDeleted;
use Flarum\Event\DiscussionWasStarted;
use Flarum\Event\PostWasDeleted;
use Flarum\Event\PostWasHidden;
use Flarum\Event\PostWasPosted;
use Flarum\Event\PostWasRestored;
use Illuminate\Contracts\Events\Dispatcher;

class UserMetadataUpdater
{
    /**
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events)
    {
        $events->listen(PostWasPosted::class, [$this, 'whenPostWasPosted']);
        $events->listen(PostWasDeleted::class, [$this, 'whenPostWasDeleted']);
        $events->listen(PostWasHidden::class, [$this, 'whenPostWasHidden']);
        $events->listen(PostWasRestored::class, [$this, 'whenPostWasRestored']);
        $events->listen(DiscussionWasStarted::class, [$this, 'whenDiscussionWasStarted']);
        $events->listen(DiscussionWasDeleted::class, [$this, 'whenDiscussionWasDeleted']);
    }

    /**
     * @param PostWasPosted $event
     */
    public function whenPostWasPosted(PostWasPosted $event)
    {
        $this->updateCommentsCount($event->post, 1);
    }

    /**
     * @param \Flarum\Event\PostWasDeleted $event
     */
    public function whenPostWasDeleted(PostWasDeleted $event)
    {
        $this->updateCommentsCount($event->post, -1);
    }

    /**
     * @param PostWasHidden $event
     */
    public function whenPostWasHidden(PostWasHidden $event)
    {
        $this->updateCommentsCount($event->post, -1);
    }

    /**
     * @param \Flarum\Event\PostWasRestored $event
     */
    public function whenPostWasRestored(PostWasRestored $event)
    {
        $this->updateCommentsCount($event->post, 1);
    }

    /**
     * @param \Flarum\Events\DiscussionWasStarted $event
     */
    public function whenDiscussionWasStarted(DiscussionWasStarted $event)
    {
        $this->updateDiscussionsCount($event->discussion, 1);
    }

    /**
     * @param \Flarum\Event\DiscussionWasDeleted $event
     */
    public function whenDiscussionWasDeleted(DiscussionWasDeleted $event)
    {
        $this->updateDiscussionsCount($event->discussion, -1);
    }

    /**
     * @param Post $post
     * @param int $amount
     */
    protected function updateCommentsCount(Post $post, $amount)
    {
        $user = $post->user;

        if ($user && $user->exists) {
            $user->comments_count += $amount;
            $user->save();
        }
    }

    /**
     * @param Discussion $discussion
     * @param int $amount
     */
    protected function updateDiscussionsCount(Discussion $discussion, $amount)
    {
        $user = $discussion->startUser;

        if ($user && $user->exists) {
            $user->discussions_count += $amount;
            $user->save();
        }
    }
}
