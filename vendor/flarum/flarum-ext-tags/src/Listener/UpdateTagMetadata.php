<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Tags\Listener;

use Flarum\Event\DiscussionWasDeleted;
use Flarum\Event\DiscussionWasStarted;
use Flarum\Event\PostWasDeleted;
use Flarum\Event\PostWasHidden;
use Flarum\Event\PostWasPosted;
use Flarum\Event\PostWasRestored;
use Flarum\Tags\Event\DiscussionWasTagged;
use Flarum\Tags\Tag;
use Illuminate\Contracts\Events\Dispatcher;

class UpdateTagMetadata
{
    /**
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events)
    {
        $events->listen(DiscussionWasStarted::class, [$this, 'whenDiscussionWasStarted']);
        $events->listen(DiscussionWasTagged::class, [$this, 'whenDiscussionWasTagged']);
        $events->listen(DiscussionWasDeleted::class, [$this, 'whenDiscussionWasDeleted']);

        $events->listen(PostWasPosted::class, [$this, 'whenPostWasPosted']);
        $events->listen(PostWasDeleted::class, [$this, 'whenPostWasDeleted']);
        $events->listen(PostWasHidden::class, [$this, 'whenPostWasHidden']);
        $events->listen(PostWasRestored::class, [$this, 'whenPostWasRestored']);
    }

    /**
     * @param DiscussionWasStarted $event
     */
    public function whenDiscussionWasStarted(DiscussionWasStarted $event)
    {
        $this->updateTags($event->discussion, 1);
    }

    /**
     * @param DiscussionWasTagged $event
     */
    public function whenDiscussionWasTagged(DiscussionWasTagged $event)
    {
        $oldTags = Tag::whereIn('id', array_pluck($event->oldTags, 'id'));

        $this->updateTags($event->discussion, -1, $oldTags);
        $this->updateTags($event->discussion, 1);
    }

    /**
     * @param DiscussionWasDeleted $event
     */
    public function whenDiscussionWasDeleted(DiscussionWasDeleted $event)
    {
        $this->updateTags($event->discussion, -1);

        $event->discussion->tags()->detach();
    }

    /**
     * @param PostWasPosted $event
     */
    public function whenPostWasPosted(PostWasPosted $event)
    {
        $this->updateTags($event->post->discussion);
    }

    /**
     * @param PostWasDeleted $event
     */
    public function whenPostWasDeleted(PostWasDeleted $event)
    {
        $this->updateTags($event->post->discussion);
    }

    /**
     * @param PostWasHidden $event
     */
    public function whenPostWasHidden(PostWasHidden $event)
    {
        $this->updateTags($event->post->discussion);
    }

    /**
     * @param PostWasRestored $event
     */
    public function whenPostWasRestored(PostWasRestored $event)
    {
        $this->updateTags($event->post->discussion);
    }

    /**
     * @param \Flarum\Core\Discussion $discussion
     * @param int $delta
     * @param Tag[]|null $tags
     */
    protected function updateTags($discussion, $delta = 0, $tags = null)
    {
        if (! $discussion) {
            return;
        }

        if (! $tags) {
            $tags = $discussion->tags;
        }

        foreach ($tags as $tag) {
            $tag->discussions_count += $delta;

            if ($discussion->last_time > $tag->last_time) {
                $tag->setLastDiscussion($discussion);
            } elseif ($discussion->id == $tag->last_discussion_id) {
                $tag->refreshLastDiscussion();
            }

            $tag->save();
        }
    }
}
