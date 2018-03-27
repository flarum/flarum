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
use Flarum\Core\Repository\DiscussionRepository;
use Flarum\Core\Support\DispatchEventsTrait;
use Flarum\Event\DiscussionWillBeDeleted;
use Illuminate\Contracts\Events\Dispatcher;

class DeleteDiscussionHandler
{
    use DispatchEventsTrait;
    use AssertPermissionTrait;

    /**
     * @var DiscussionRepository
     */
    protected $discussions;

    /**
     * @param Dispatcher $events
     * @param DiscussionRepository $discussions
     */
    public function __construct(Dispatcher $events, DiscussionRepository $discussions)
    {
        $this->events = $events;
        $this->discussions = $discussions;
    }

    /**
     * @param DeleteDiscussion $command
     * @return \Flarum\Core\Discussion
     * @throws PermissionDeniedException
     */
    public function handle(DeleteDiscussion $command)
    {
        $actor = $command->actor;

        $discussion = $this->discussions->findOrFail($command->discussionId, $actor);

        $this->assertCan($actor, 'delete', $discussion);

        $this->events->fire(
            new DiscussionWillBeDeleted($discussion, $actor, $command->data)
        );

        $discussion->delete();

        $this->dispatchEventsFor($discussion, $actor);

        return $discussion;
    }
}
