<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Approval\Listener;

use Flarum\Core\Discussion;
use Flarum\Event\ScopeHiddenDiscussionVisibility;
use Flarum\Event\ScopeModelVisibility;
use Flarum\Event\ScopePostVisibility;
use Illuminate\Contracts\Events\Dispatcher;

class HideUnapprovedContent
{
    /**
     * @var Dispatcher
     */
    protected $events;

    /**
     * @param Dispatcher $events
     */
    public function __construct(Dispatcher $events)
    {
        $this->events = $events;
    }

    /**
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events)
    {
        $events->listen(ScopeModelVisibility::class, [$this, 'hideUnapprovedDiscussions']);
        $events->listen(ScopePostVisibility::class, [$this, 'hideUnapprovedPosts']);
    }

    /**
     * @param ScopeModelVisibility $event
     */
    public function hideUnapprovedDiscussions(ScopeModelVisibility $event)
    {
        if ($event->model instanceof Discussion) {
            $user = $event->actor;

            if (! $user->hasPermission('discussion.editPosts')) {
                $event->query->where(function ($query) use ($user) {
                    $query->where('discussions.is_approved', 1)
                        ->orWhere('start_user_id', $user->id);

                    $this->events->fire(
                        new ScopeHiddenDiscussionVisibility($query, $user, 'discussion.editPosts')
                    );
                });
            }
        }
    }

    /**
     * @param ScopePostVisibility $event
     */
    public function hideUnapprovedPosts(ScopePostVisibility $event)
    {
        if ($event->actor->can('editPosts', $event->discussion)) {
            return;
        }

        $event->query->where(function ($query) use ($event) {
            $query->where('posts.is_approved', 1)
                ->orWhere('user_id', $event->actor->id);
        });
    }
}
