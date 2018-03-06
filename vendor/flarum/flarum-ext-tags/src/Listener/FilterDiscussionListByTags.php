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

use Flarum\Event\ConfigureDiscussionGambits;
use Flarum\Event\ConfigureDiscussionSearch;
use Flarum\Tags\Gambit\TagGambit;
use Flarum\Tags\Tag;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Query\Expression;

class FilterDiscussionListByTags
{
    /**
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events)
    {
        $events->listen(ConfigureDiscussionGambits::class, [$this, 'addTagGambit']);
        $events->listen(ConfigureDiscussionSearch::class, [$this, 'hideTagsFromDiscussionList']);
    }

    /**
     * @param ConfigureDiscussionGambits $event
     */
    public function addTagGambit(ConfigureDiscussionGambits $event)
    {
        $event->gambits->add(TagGambit::class);
    }

    /**
     * @param ConfigureDiscussionSearch $event
     */
    public function hideTagsFromDiscussionList(ConfigureDiscussionSearch $event)
    {
        $query = $event->search->getQuery();

        foreach ($event->search->getActiveGambits() as $gambit) {
            if ($gambit instanceof TagGambit) {
                return;
            }
        }

        $query->whereNotExists(function ($query) {
            return $query->select(new Expression(1))
                ->from('discussions_tags')
                ->whereIn('tag_id', Tag::where('is_hidden', 1)->lists('id'))
                ->where('discussions.id', new Expression('discussion_id'));
        });
    }
}
