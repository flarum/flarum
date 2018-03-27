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

use Flarum\Api\Serializer\DiscussionBasicSerializer;
use Flarum\Api\Serializer\PostBasicSerializer;
use Flarum\Api\Serializer\PostSerializer;
use Flarum\Event\PrepareApiAttributes;
use Illuminate\Contracts\Events\Dispatcher;

class AddPostApprovalAttributes
{
    /**
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events)
    {
        $events->listen(PrepareApiAttributes::class, [$this, 'addApiAttributes']);
    }

    /**
     * @param PrepareApiAttributes $event
     */
    public function addApiAttributes(PrepareApiAttributes $event)
    {
        if ($event->isSerializer(DiscussionBasicSerializer::class)
            || $event->isSerializer(PostBasicSerializer::class)) {
            $event->attributes['isApproved'] = (bool) $event->model->is_approved;
        }

        if ($event->isSerializer(PostSerializer::class)) {
            $event->attributes['canApprove'] = (bool) $event->actor->can('approvePosts', $event->model->discussion);
        }
    }
}
