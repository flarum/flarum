<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Subscriptions\Listener;

use Flarum\Event\ConfigureDiscussionGambits;
use Flarum\Event\ConfigureDiscussionSearch;
use Flarum\Event\ConfigureForumRoutes;
use Flarum\Subscriptions\Gambit\SubscriptionGambit;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Query\Expression;

class FilterDiscussionListBySubscription
{
    /**
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events)
    {
        $events->listen(ConfigureDiscussionGambits::class, [$this, 'addGambit']);
        $events->listen(ConfigureDiscussionSearch::class, [$this, 'filterIgnored']);
        $events->listen(ConfigureForumRoutes::class, [$this, 'addRoutes']);
    }

    /**
     * @param ConfigureDiscussionGambits $event
     */
    public function addGambit(ConfigureDiscussionGambits $event)
    {
        $event->gambits->add(SubscriptionGambit::class);
    }

    /**
     * @param ConfigureDiscussionSearch $event
     */
    public function filterIgnored(ConfigureDiscussionSearch $event)
    {
        if (! $event->criteria->query) {
            // might be better as `id IN (subquery)`?
            $actor = $event->search->getActor();
            $event->search->getQuery()->whereNotExists(function ($query) use ($actor) {
                $query->selectRaw(1)
                      ->from('users_discussions')
                      ->where('discussions.id', new Expression('discussion_id'))
                      ->where('user_id', $actor->id)
                      ->where('subscription', 'ignore');
            });
        }
    }

    /**
     * @param ConfigureForumRoutes $event
     */
    public function addRoutes(ConfigureForumRoutes $event)
    {
        $event->get('/following', 'following');
    }
}
