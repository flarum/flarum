<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Flags\Listener;

use Flarum\Api\Serializer\CurrentUserSerializer;
use Flarum\Api\Serializer\ForumSerializer;
use Flarum\Api\Serializer\PostSerializer;
use Flarum\Core\User;
use Flarum\Event\ConfigureApiRoutes;
use Flarum\Event\ConfigureModelDates;
use Flarum\Event\PrepareApiAttributes;
use Flarum\Flags\Api\Controller;
use Flarum\Flags\Flag;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Events\Dispatcher;

class AddFlagsApi
{
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
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events)
    {
        $events->listen(ConfigureModelDates::class, [$this, 'configureModelDates']);
        $events->listen(PrepareApiAttributes::class, [$this, 'prepareApiAttributes']);
        $events->listen(ConfigureApiRoutes::class, [$this, 'configureApiRoutes']);
    }

    /**
     * @param ConfigureModelDates $event
     */
    public function configureModelDates(ConfigureModelDates $event)
    {
        if ($event->isModel(User::class)) {
            $event->dates[] = 'flags_read_time';
        }
    }

    /**
     * @param PrepareApiAttributes $event
     */
    public function prepareApiAttributes(PrepareApiAttributes $event)
    {
        if ($event->isSerializer(ForumSerializer::class)) {
            $event->attributes['canViewFlags'] = $event->actor->hasPermissionLike('discussion.viewFlags');

            if ($event->attributes['canViewFlags']) {
                $event->attributes['flagsCount'] = (int) $this->getFlagsCount($event->actor);
            }

            $event->attributes['guidelinesUrl'] = $this->settings->get('flarum-flags.guidelines_url');
        }

        if ($event->isSerializer(CurrentUserSerializer::class)) {
            $event->attributes['newFlagsCount'] = (int) $this->getNewFlagsCount($event->model);
        }

        if ($event->isSerializer(PostSerializer::class)) {
            $event->attributes['canFlag'] = $event->actor->can('flag', $event->model);
        }
    }

    /**
     * @param ConfigureApiRoutes $event
     */
    public function configureApiRoutes(ConfigureApiRoutes $event)
    {
        $event->get('/flags', 'flags.index', Controller\ListFlagsController::class);
        $event->post('/flags', 'flags.create', Controller\CreateFlagController::class);
        $event->delete('/posts/{id}/flags', 'flags.delete', Controller\DeleteFlagsController::class);
    }

    /**
     * @param User $actor
     * @return int
     */
    protected function getFlagsCount(User $actor)
    {
        return Flag::whereVisibleTo($actor)->distinct()->count('flags.post_id');
    }

    /**
     * @param User $actor
     * @return int
     */
    protected function getNewFlagsCount(User $actor)
    {
        $query = Flag::whereVisibleTo($actor);

        if ($time = $actor->flags_read_time) {
            $query->where('flags.time', '>', $time);
        }

        return $query->distinct()->count('flags.post_id');
    }
}
