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

use Flarum\Core\Post;
use Flarum\Event\PostWasDeleted;
use Flarum\Event\PostWasHidden;
use Flarum\Event\PostWasPosted;
use Flarum\Event\PostWasRestored;
use Illuminate\Contracts\Events\Dispatcher;

class DiscussionMetadataUpdater
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
    }

    /**
     * @param PostWasPosted $event
     */
    public function whenPostWasPosted(PostWasPosted $event)
    {
        $discussion = $event->post->discussion;

        if ($discussion && $discussion->exists) {
            $discussion->refreshCommentsCount();
            $discussion->refreshLastPost();
            $discussion->refreshParticipantsCount();
            $discussion->save();
        }
    }

    /**
     * @param \Flarum\Event\PostWasDeleted $event
     */
    public function whenPostWasDeleted(PostWasDeleted $event)
    {
        $this->removePost($event->post);

        $discussion = $event->post->discussion;

        if ($discussion && $discussion->posts()->count() === 0) {
            $discussion->delete();
        }
    }

    /**
     * @param PostWasHidden $event
     */
    public function whenPostWasHidden(PostWasHidden $event)
    {
        $this->removePost($event->post);
    }

    /**
     * @param PostWasRestored $event
     */
    public function whenPostWasRestored(PostWasRestored $event)
    {
        $discussion = $event->post->discussion;

        if ($discussion && $discussion->exists) {
            $discussion->refreshCommentsCount();
            $discussion->refreshParticipantsCount();
            $discussion->refreshLastPost();
            $discussion->save();
        }
    }

    /**
     * @param Post $post
     */
    protected function removePost(Post $post)
    {
        $discussion = $post->discussion;

        if ($discussion && $discussion->exists) {
            $discussion->refreshCommentsCount();
            $discussion->refreshParticipantsCount();

            if ($discussion->last_post_id == $post->id) {
                $discussion->refreshLastPost();
            }

            $discussion->save();
        }
    }
}
