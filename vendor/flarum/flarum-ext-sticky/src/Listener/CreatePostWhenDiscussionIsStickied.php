<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Sticky\Listener;

use Flarum\Core\Discussion;
use Flarum\Core\User;
use Flarum\Event\ConfigurePostTypes;
use Flarum\Sticky\Event\DiscussionWasStickied;
use Flarum\Sticky\Event\DiscussionWasUnstickied;
use Flarum\Sticky\Post\DiscussionStickiedPost;
use Illuminate\Contracts\Events\Dispatcher;

class CreatePostWhenDiscussionIsStickied
{
    /**
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events)
    {
        $events->listen(ConfigurePostTypes::class, [$this, 'configurePostTypes']);
        $events->listen(DiscussionWasStickied::class, [$this, 'whenDiscussionWasStickied']);
        $events->listen(DiscussionWasUnstickied::class, [$this, 'whenDiscussionWasUnstickied']);
    }

    /**
     * @param ConfigurePostTypes $event
     */
    public function configurePostTypes(ConfigurePostTypes $event)
    {
        $event->add(DiscussionStickiedPost::class);
    }

    /**
     * @param DiscussionWasStickied $event
     */
    public function whenDiscussionWasStickied(DiscussionWasStickied $event)
    {
        $this->stickyChanged($event->discussion, $event->user, true);
    }

    /**
     * @param DiscussionWasUnstickied $event
     */
    public function whenDiscussionWasUnstickied(DiscussionWasUnstickied $event)
    {
        $this->stickyChanged($event->discussion, $event->user, false);
    }

    /**
     * @param Discussion $discussion
     * @param User $user
     * @param bool $isSticky
     */
    protected function stickyChanged(Discussion $discussion, User $user, $isSticky)
    {
        $post = DiscussionStickiedPost::reply(
            $discussion->id,
            $user->id,
            $isSticky
        );

        $discussion->mergePost($post);
    }
}
