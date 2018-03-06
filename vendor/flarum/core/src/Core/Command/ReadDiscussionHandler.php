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
use Flarum\Core\Repository\DiscussionRepository;
use Flarum\Core\Support\DispatchEventsTrait;
use Flarum\Event\DiscussionStateWillBeSaved;
use Illuminate\Contracts\Events\Dispatcher;

class ReadDiscussionHandler
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
     * @param ReadDiscussion $command
     * @return \Flarum\Core\DiscussionState
     * @throws \Flarum\Core\Exception\PermissionDeniedException
     */
    public function handle(ReadDiscussion $command)
    {
        $actor = $command->actor;

        $this->assertRegistered($actor);

        $discussion = $this->discussions->findOrFail($command->discussionId, $actor);

        $state = $discussion->stateFor($actor);
        $state->read($command->readNumber);

        $this->events->fire(
            new DiscussionStateWillBeSaved($state)
        );

        $state->save();

        $this->dispatchEventsFor($state);

        return $state;
    }
}
