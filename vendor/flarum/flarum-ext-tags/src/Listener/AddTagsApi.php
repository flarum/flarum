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

use Flarum\Event\ConfigureApiRoutes;
use Flarum\Tags\Api\Controller;
use Illuminate\Contracts\Events\Dispatcher;

class AddTagsApi
{
    public function subscribe(Dispatcher $events)
    {
        $events->listen(ConfigureApiRoutes::class, [$this, 'configureApiRoutes']);
    }

    public function configureApiRoutes(ConfigureApiRoutes $event)
    {
        $event->get('/tags', 'tags.index', Controller\ListTagsController::class);
        $event->post('/tags', 'tags.create', Controller\CreateTagController::class);
        $event->post('/tags/order', 'tags.order', Controller\OrderTagsController::class);
        $event->patch('/tags/{id}', 'tags.update', Controller\UpdateTagController::class);
        $event->delete('/tags/{id}', 'tags.delete', Controller\DeleteTagController::class);
    }
}
