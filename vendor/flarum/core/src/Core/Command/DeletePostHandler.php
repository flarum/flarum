<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Core\Command;

use Flarum\Core\Access\AssertPermissionTrait;
use Flarum\Core\Exception\PermissionDeniedException;
use Flarum\Core\Repository\PostRepository;
use Flarum\Core\Support\DispatchEventsTrait;
use Flarum\Event\PostWillBeDeleted;
use Illuminate\Contracts\Events\Dispatcher;

class DeletePostHandler
{
    use DispatchEventsTrait;
    use AssertPermissionTrait;

    /**
     * @var PostRepository
     */
    protected $posts;

    /**
     * @param Dispatcher $events
     * @param PostRepository $posts
     */
    public function __construct(Dispatcher $events, PostRepository $posts)
    {
        $this->events = $events;
        $this->posts = $posts;
    }

    /**
     * @param DeletePost $command
     * @return \Flarum\Core\Post
     * @throws PermissionDeniedException
     */
    public function handle(DeletePost $command)
    {
        $actor = $command->actor;

        $post = $this->posts->findOrFail($command->postId, $actor);

        $this->assertCan($actor, 'delete', $post);

        $this->events->fire(
            new PostWillBeDeleted($post, $actor, $command->data)
        );

        $post->delete();

        $this->dispatchEventsFor($post, $actor);

        return $post;
    }
}
