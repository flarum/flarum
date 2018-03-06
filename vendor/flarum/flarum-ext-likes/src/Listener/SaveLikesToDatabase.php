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

use Flarum\Core\Access\AssertPermissionTrait;
use Flarum\Event\PostWasDeleted;
use Flarum\Event\PostWillBeSaved;
use Flarum\Likes\Event\PostWasLiked;
use Flarum\Likes\Event\PostWasUnliked;
use Illuminate\Contracts\Events\Dispatcher;

class SaveLikesToDatabase
{
    use AssertPermissionTrait;

    /**
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events)
    {
        $events->listen(PostWillBeSaved::class, [$this, 'whenPostWillBeSaved']);
        $events->listen(PostWasDeleted::class, [$this, 'whenPostWasDeleted']);
    }

    /**
     * @param PostWillBeSaved $event
     */
    public function whenPostWillBeSaved(PostWillBeSaved $event)
    {
        $post = $event->post;
        $data = $event->data;

        if ($post->exists && isset($data['attributes']['isLiked'])) {
            $actor = $event->actor;
            $liked = (bool) $data['attributes']['isLiked'];

            $this->assertCan($actor, 'like', $post);

            $currentlyLiked = $post->likes()->where('user_id', $actor->id)->exists();

            if ($liked && ! $currentlyLiked) {
                $post->likes()->attach($actor->id);

                $post->raise(new PostWasLiked($post, $actor));
            } elseif ($currentlyLiked) {
                $post->likes()->detach($actor->id);

                $post->raise(new PostWasUnliked($post, $actor));
            }
        }
    }

    /**
     * @param PostWasDeleted $event
     */
    public function whenPostWasDeleted(PostWasDeleted $event)
    {
        $event->post->likes()->detach();
    }
}
