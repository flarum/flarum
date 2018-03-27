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

use Flarum\Event\ConfigureDiscussionGambits;
use Flarum\Event\ConfigureDiscussionSearch;
use Flarum\Sticky\Gambit\StickyGambit;
use Flarum\Tags\Gambit\TagGambit;
use Illuminate\Contracts\Events\Dispatcher;

class PinStickiedDiscussionsToTop
{
    /**
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events)
    {
        $events->listen(ConfigureDiscussionGambits::class, [$this, 'addStickyGambit']);
        $events->listen(ConfigureDiscussionSearch::class, [$this, 'reorderSearch']);
    }

    /**
     * @param ConfigureDiscussionGambits $event
     */
    public function addStickyGambit(ConfigureDiscussionGambits $event)
    {
        $event->gambits->add(StickyGambit::class);
    }

    /**
     * @param ConfigureDiscussionSearch $event
     */
    public function reorderSearch(ConfigureDiscussionSearch $event)
    {
        if ($event->criteria->sort === null) {
            $search = $event->search;
            $query = $search->getQuery();

            if (! is_array($query->orders)) {
                $query->orders = [];
            }

            foreach ($search->getActiveGambits() as $gambit) {
                if ($gambit instanceof TagGambit) {
                    array_unshift($query->orders, ['column' => 'is_sticky', 'direction' => 'desc']);

                    return;
                }
            }

            $query->leftJoin('users_discussions', function ($join) use ($search) {
                $join->on('users_discussions.discussion_id', '=', 'discussions.id')
                     ->where('discussions.is_sticky', '=', true)
                     ->where('users_discussions.user_id', '=', $search->getActor()->id);
            });

            // TODO: Might be quicker to do a subquery in the order clause than a join?
            $grammar = $query->getGrammar();
            $readNumber = $grammar->wrap('users_discussions.read_number');
            $lastPostNumber = $grammar->wrap('discussions.last_post_number');

            array_unshift($query->orders, [
                'type' => 'raw',
                'sql' => "(is_sticky AND ($readNumber IS NULL OR $lastPostNumber > $readNumber)) desc"
            ]);
        }
    }
}
