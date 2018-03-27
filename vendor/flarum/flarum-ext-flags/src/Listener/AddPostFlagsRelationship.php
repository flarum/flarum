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

use Flarum\Api\Controller;
use Flarum\Api\Serializer\PostSerializer;
use Flarum\Core\Post;
use Flarum\Event\ConfigureApiController;
use Flarum\Event\GetApiRelationship;
use Flarum\Event\GetModelRelationship;
use Flarum\Event\PostWasDeleted;
use Flarum\Event\PrepareApiData;
use Flarum\Flags\Api\Controller\CreateFlagController;
use Flarum\Flags\Api\Serializer\FlagSerializer;
use Flarum\Flags\Flag;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Collection;

class AddPostFlagsRelationship
{
    /**
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events)
    {
        $events->listen(GetModelRelationship::class, [$this, 'getModelRelationship']);
        $events->listen(PostWasDeleted::class, [$this, 'postWasDeleted']);
        $events->listen(GetApiRelationship::class, [$this, 'getApiRelationship']);
        $events->listen(ConfigureApiController::class, [$this, 'includeFlagsRelationship']);
        $events->listen(PrepareApiData::class, [$this, 'prepareApiData']);
    }

    /**
     * @param GetModelRelationship $event
     * @return \Illuminate\Database\Eloquent\Relations\HasMany|null
     */
    public function getModelRelationship(GetModelRelationship $event)
    {
        if ($event->isRelationship(Post::class, 'flags')) {
            return $event->model->hasMany(Flag::class, 'post_id');
        }
    }

    /**
     * @param PostWasDeleted $event
     */
    public function postWasDeleted(PostWasDeleted $event)
    {
        $event->post->flags()->delete();
    }

    /**
     * @param GetApiRelationship $event
     * @return \Tobscure\JsonApi\Relationship|null
     */
    public function getApiRelationship(GetApiRelationship $event)
    {
        if ($event->isRelationship(PostSerializer::class, 'flags')) {
            return $event->serializer->hasMany($event->model, FlagSerializer::class, 'flags');
        }
    }

    /**
     * @param ConfigureApiController $event
     */
    public function includeFlagsRelationship(ConfigureApiController $event)
    {
        if ($event->isController(Controller\ShowDiscussionController::class)) {
            $event->addInclude([
                'posts.flags',
                'posts.flags.user'
            ]);
        }

        if ($event->isController(Controller\ListPostsController::class)
            || $event->isController(Controller\ShowPostController::class)) {
            $event->addInclude([
                'flags',
                'flags.user'
            ]);
        }
    }

    /**
     * @param PrepareApiData $event
     */
    public function prepareApiData(PrepareApiData $event)
    {
        // For any API action that allows the 'flags' relationship to be
        // included, we need to preload this relationship onto the data (Post
        // models) so that we can selectively expose only the flags that the
        // user has permission to view.
        if ($event->isController(Controller\ShowDiscussionController::class)) {
            $posts = $event->data->getRelation('posts');
        }

        if ($event->isController(Controller\ListPostsController::class)) {
            $posts = $event->data->all();
        }

        if ($event->isController(Controller\ShowPostController::class)) {
            $posts = [$event->data];
        }

        if ($event->isController(CreateFlagController::class)) {
            $posts = [$event->data->post];
        }

        if (isset($posts)) {
            $actor = $event->request->getAttribute('actor');
            $postsWithPermission = [];

            foreach ($posts as $post) {
                if (is_object($post)) {
                    $post->setRelation('flags', null);

                    if ($actor->can('viewFlags', $post->discussion)) {
                        $postsWithPermission[] = $post;
                    }
                }
            }

            if (count($postsWithPermission)) {
                (new Collection($postsWithPermission))
                    ->load('flags', 'flags.user');
            }
        }
    }
}
