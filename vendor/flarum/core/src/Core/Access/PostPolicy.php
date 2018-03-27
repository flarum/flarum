<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Core\Access;

use Carbon\Carbon;
use Flarum\Core\Post;
use Flarum\Core\User;
use Flarum\Event\ScopePostVisibility;
use Flarum\Event\ScopePrivatePostVisibility;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Events\Dispatcher;

class PostPolicy extends AbstractPolicy
{
    /**
     * {@inheritdoc}
     */
    protected $model = Post::class;

    /**
     * @var SettingsRepositoryInterface
     */
    protected $settings;
    /**
     * @var Dispatcher
     */
    protected $events;

    /**
     * @param SettingsRepositoryInterface $settings
     */
    public function __construct(SettingsRepositoryInterface $settings, Dispatcher $events)
    {
        $this->settings = $settings;
        $this->events = $events;
    }

    /**
     * {@inheritdoc}
     */
    public function subscribe(Dispatcher $events)
    {
        parent::subscribe($events);

        $events->listen(ScopePostVisibility::class, [$this, 'scopePostVisibility']);
    }

    /**
     * @param User $actor
     * @param string $ability
     * @param Post $post
     * @return bool|null
     */
    public function after(User $actor, $ability, Post $post)
    {
        if ($actor->can($ability.'Posts', $post->discussion)) {
            return true;
        }
    }

    /**
     * @param ScopePostVisibility $event
     */
    public function scopePostVisibility(ScopePostVisibility $event)
    {
        // Hide private posts per default.
        $event->query->where(function ($query) use ($event) {
            $query->where('posts.is_private', false);

            $this->events->fire(
                new ScopePrivatePostVisibility($event->discussion, $query, $event->actor)
            );
        });

        // When fetching a discussion's posts: if the user doesn't have permission
        // to moderate the discussion, then they can't see posts that have been
        // hidden by someone other than themself.
        if ($event->actor->cannot('editPosts', $event->discussion)) {
            $event->query->where(function ($query) use ($event) {
                $query->whereNull('hide_time')
                    ->orWhere('user_id', $event->actor->id);
            });
        }
    }

    /**
     * @param User $actor
     * @param Post $post
     * @return bool|null
     */
    public function edit(User $actor, Post $post)
    {
        // A post is allowed to be edited if the user has permission to moderate
        // the discussion which it's in, or if they are the author and the post
        // hasn't been deleted by someone else.
        if ($post->user_id == $actor->id && (! $post->hide_time || $post->hide_user_id == $actor->id)) {
            $allowEditing = $this->settings->get('allow_post_editing');

            if ($allowEditing === '-1'
                || ($allowEditing === 'reply' && $post->number >= $post->discussion->last_post_number)
                || ($post->time->diffInMinutes(new Carbon) < $allowEditing)) {
                return true;
            }
        }
    }
}
