<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Mentions\Listener;

use Flarum\Api\Controller\CreatePostController;
use Flarum\Api\Controller\ListPostsController;
use Flarum\Api\Controller\ShowDiscussionController;
use Flarum\Api\Controller\ShowPostController;
use Flarum\Api\Serializer\PostBasicSerializer;
use Flarum\Core\Post;
use Flarum\Core\Repository\PostRepository;
use Flarum\Core\User;
use Flarum\Event\ConfigureApiController;
use Flarum\Event\GetApiRelationship;
use Flarum\Event\GetModelRelationship;
use Flarum\Event\PrepareApiData;
use Illuminate\Contracts\Events\Dispatcher;

class AddPostMentionedByRelationship
{
    /**
     * @var PostRepository
     */
    protected $posts;

    /**
     * @param PostRepository $posts
     */
    public function __construct(PostRepository $posts)
    {
        $this->posts = $posts;
    }

    /**
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events)
    {
        $events->listen(GetModelRelationship::class, [$this, 'getModelRelationship']);
        $events->listen(GetApiRelationship::class, [$this, 'getApiRelationship']);
        $events->listen(ConfigureApiController::class, [$this, 'includeRelationships']);
        $events->listen(PrepareApiData::class, [$this, 'filterVisiblePosts']);
    }

    /**
     * @param GetModelRelationship $event
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany|null
     */
    public function getModelRelationship(GetModelRelationship $event)
    {
        if ($event->isRelationship(Post::class, 'mentionedBy')) {
            return $event->model->belongsToMany(Post::class, 'mentions_posts', 'mentions_id', 'post_id', 'mentionedBy');
        }

        if ($event->isRelationship(Post::class, 'mentionsPosts')) {
            return $event->model->belongsToMany(Post::class, 'mentions_posts', 'post_id', 'mentions_id', 'mentionsPosts');
        }

        if ($event->isRelationship(Post::class, 'mentionsUsers')) {
            return $event->model->belongsToMany(User::class, 'mentions_users', 'post_id', 'mentions_id', 'mentionsUsers');
        }
    }

    /**
     * @param GetApiRelationship $event
     * @return \Tobscure\JsonApi\Relationship|null
     */
    public function getApiRelationship(GetApiRelationship $event)
    {
        if ($event->isRelationship(PostBasicSerializer::class, 'mentionedBy')) {
            return $event->serializer->hasMany($event->model, PostBasicSerializer::class, 'mentionedBy');
        }

        if ($event->isRelationship(PostBasicSerializer::class, 'mentionsPosts')) {
            return $event->serializer->hasMany($event->model, PostBasicSerializer::class, 'mentionsPosts');
        }

        if ($event->isRelationship(PostBasicSerializer::class, 'mentionsUsers')) {
            return $event->serializer->hasMany($event->model, PostBasicSerializer::class, 'mentionsUsers');
        }
    }

    /**
     * @param ConfigureApiController $event
     */
    public function includeRelationships(ConfigureApiController $event)
    {
        if ($event->isController(ShowDiscussionController::class)) {
            $event->addInclude([
                'posts.mentionedBy',
                'posts.mentionedBy.user',
                'posts.mentionedBy.discussion'
            ]);
        }

        if ($event->isController(ShowPostController::class)
            || $event->isController(ListPostsController::class)) {
            $event->addInclude([
                'mentionedBy',
                'mentionedBy.user',
                'mentionedBy.discussion'
            ]);
        }

        if ($event->isController(CreatePostController::class)) {
            $event->addInclude([
                'mentionsPosts',
                'mentionsPosts.mentionedBy'
            ]);
        }
    }

    /**
     * Apply visibility permissions to API data.
     *
     * Each post in an API document has a relationship with posts that have
     * mentioned it (mentionedBy). This listener will manually filter these
     * additional posts so that the user can't see any posts which they don't
     * have access to.
     *
     * @param PrepareApiData $event
     */
    public function filterVisiblePosts(PrepareApiData $event)
    {
        // Firstly we gather a list of posts contained within the API document.
        // This will vary according to the API endpoint that is being accessed.
        if ($event->isController(ShowDiscussionController::class)) {
            $posts = $event->data->posts;
        } elseif ($event->isController(ShowPostController::class)) {
            $posts = [$event->data];
        } elseif ($event->isController(ListPostsController::class)) {
            $posts = $event->data;
        }

        if (isset($posts)) {
            $posts = array_filter((array) $posts, 'is_object');
            $ids = [];

            // Once we have the posts, construct a list of the IDs of all of
            // the posts that they have been mentioned in. We can then filter
            // this list of IDs to weed out all of the ones which the user is
            // not meant to see.
            foreach ($posts as $post) {
                $ids = array_merge($ids, $post->mentionedBy->pluck('id')->all());
            }

            $ids = $this->posts->filterVisibleIds($ids, $event->actor);

            // Finally, go back through each of the posts and filter out any
            // of the posts in the relationship data that we now know are
            // invisible to the user.
            foreach ($posts as $post) {
                $post->setRelation('mentionedBy', $post->mentionedBy->filter(function ($post) use ($ids) {
                    return array_search($post->id, $ids) !== false;
                }));
            }
        }
    }
}
