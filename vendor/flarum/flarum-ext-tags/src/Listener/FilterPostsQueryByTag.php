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

use Flarum\Event\ConfigurePostsQuery;
use Illuminate\Contracts\Events\Dispatcher;

class FilterPostsQueryByTag
{
    /**
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events)
    {
        $events->listen(ConfigurePostsQuery::class, [$this, 'filterQuery']);
    }

    /**
     * @param ConfigurePostsQuery $event
     */
    public function filterQuery(ConfigurePostsQuery $event)
    {
        if ($tagId = array_get($event->filter, 'tag')) {
            $event->query
                ->join('discussions_tags', 'discussions_tags.discussion_id', '=', 'posts.discussion_id')
                ->where('discussions_tags.tag_id', $tagId);
        }
    }
}
