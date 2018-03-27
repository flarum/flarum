<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Flags\Command;

use Flarum\Core\Access\AssertPermissionTrait;
use Flarum\Core\Repository\PostRepository;
use Flarum\Flags\Event\FlagsWillBeDeleted;
use Flarum\Flags\Flag;
use Illuminate\Contracts\Events\Dispatcher;

class DeleteFlagsHandler
{
    use AssertPermissionTrait;

    /**
     * @var PostRepository
     */
    protected $posts;

    /**
     * @var Dispatcher
     */
    protected $events;

    /**
     * @param PostRepository $posts
     * @param Dispatcher $events
     */
    public function __construct(PostRepository $posts, Dispatcher $events)
    {
        $this->posts = $posts;
        $this->events = $events;
    }

    /**
     * @param DeleteFlags $command
     * @return Flag
     */
    public function handle(DeleteFlags $command)
    {
        $actor = $command->actor;

        $post = $this->posts->findOrFail($command->postId, $actor);

        $this->assertCan($actor, 'viewFlags', $post->discussion);

        $this->events->fire(new FlagsWillBeDeleted($post, $actor, $command->data));

        $post->flags()->delete();

        return $post;
    }
}
