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

use Flarum\Api\Controller\ListDiscussionsController;
use Flarum\Api\Serializer\DiscussionSerializer;
use Flarum\Event\ConfigureApiController;
use Flarum\Event\PrepareApiAttributes;
use Illuminate\Contracts\Events\Dispatcher;

class AddApiAttributes
{
    /**
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events)
    {
        $events->listen(PrepareApiAttributes::class, [$this, 'prepareApiAttributes']);
        $events->listen(ConfigureApiController::class, [$this, 'includeStartPost']);
    }

    /**
     * @param PrepareApiAttributes $event
     */
    public function prepareApiAttributes(PrepareApiAttributes $event)
    {
        if ($event->isSerializer(DiscussionSerializer::class)) {
            $event->attributes['isSticky'] = (bool) $event->model->is_sticky;
            $event->attributes['canSticky'] = (bool) $event->actor->can('sticky', $event->model);
        }
    }

    /**
     * @param ConfigureApiController $event
     */
    public function includeStartPost(ConfigureApiController $event)
    {
        if ($event->isController(ListDiscussionsController::class)) {
            $event->addInclude('startPost');
        }
    }
}
