<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Tags\Access;

use Carbon\Carbon;
use Flarum\Core\Access\AbstractPolicy;
use Flarum\Core\Discussion;
use Flarum\Core\User;
use Flarum\Event\ScopeHiddenDiscussionVisibility;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\Tags\Tag;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Expression;

class DiscussionPolicy extends AbstractPolicy
{
    /**
     * {@inheritdoc}
     */
    protected $model = Discussion::class;

    /**
     * @var SettingsRepositoryInterface
     */
    protected $settings;

    /**
     * @param SettingsRepositoryInterface $settings
     */
    public function __construct(SettingsRepositoryInterface $settings)
    {
        $this->settings = $settings;
    }

    /**
     * {@inheritdoc}
     */
    public function subscribe(Dispatcher $events)
    {
        parent::subscribe($events);

        $events->listen(ScopeHiddenDiscussionVisibility::class, [$this, 'scopeHiddenDiscussionVisibility']);
    }

    /**
     * @param User $actor
     * @param string $ability
     * @param Discussion $discussion
     * @return bool
     */
    public function after(User $actor, $ability, Discussion $discussion)
    {
        // Wrap all discussion permission checks with some logic pertaining to
        // the discussion's tags. If the discussion has a tag that has been
        // restricted, the user must have the permission for that tag.
        $tags = $discussion->tags;

        if (count($tags)) {
            $restricted = false;

            foreach ($tags as $tag) {
                if ($tag->is_restricted) {
                    if (! $actor->hasPermission('tag'.$tag->id.'.discussion.'.$ability)) {
                        return false;
                    }

                    $restricted = true;
                }
            }

            if ($restricted) {
                return true;
            }
        }
    }

    /**
     * @param User $actor
     * @param Builder $query
     */
    public function find(User $actor, Builder $query)
    {
        // Hide discussions which have tags that the user is not allowed to see.
        $query->whereNotExists(function ($query) use ($actor) {
            return $query->select(new Expression(1))
                ->from('discussions_tags')
                ->whereIn('tag_id', Tag::getIdsWhereCannot($actor, 'viewDiscussions'))
                ->where('discussions.id', new Expression('discussion_id'));
        });

        // Hide discussions with no tags if the user doesn't have that global
        // permission.
        if (! $actor->hasPermission('viewDiscussions')) {
            $query->has('tags');
        }
    }

    /**
     * @param ScopeHiddenDiscussionVisibility $event
     */
    public function scopeHiddenDiscussionVisibility(ScopeHiddenDiscussionVisibility $event)
    {
        // By default, discussions are not visible to the public if they are
        // hidden or contain zero comments - unless the actor has a certain
        // permission. Since we grant permissions per-tag, we will make
        // discussions visible in the tags for which the user has that
        // permission.
        $event->query->orWhereExists(function ($query) use ($event) {
            return $query->select(new Expression(1))
                ->from('discussions_tags')
                ->whereIn('tag_id', Tag::getIdsWhereCan($event->actor, $event->permission))
                ->where('discussions.id', new Expression('discussion_id'));
        });
    }

    /**
     * This method checks, if the user is still allowed to edit the tags
     * based on the configuration item.
     *
     * @param User $actor
     * @param Discussion $discussion
     * @return bool
     */
    public function tag(User $actor, Discussion $discussion)
    {
        if ($discussion->start_user_id == $actor->id) {
            $allowEditTags = $this->settings->get('allow_tag_change');

            if ($allowEditTags === '-1'
                || ($allowEditTags === 'reply' && $discussion->participants_count <= 1)
                || ($discussion->start_time->diffInMinutes(new Carbon) < $allowEditTags)
            ) {
                return true;
            }
        }
    }
}
