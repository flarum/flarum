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
use Flarum\Core\Repository\GroupRepository;
use Flarum\Core\Support\DispatchEventsTrait;
use Flarum\Event\GroupWillBeDeleted;
use Illuminate\Contracts\Events\Dispatcher;

class DeleteGroupHandler
{
    use DispatchEventsTrait;
    use AssertPermissionTrait;

    /**
     * @var GroupRepository
     */
    protected $groups;

    /**
     * @param GroupRepository $groups
     */
    public function __construct(Dispatcher $events, GroupRepository $groups)
    {
        $this->groups = $groups;
        $this->events = $events;
    }

    /**
     * @param DeleteGroup $command
     * @return \Flarum\Core\Group
     * @throws PermissionDeniedException
     */
    public function handle(DeleteGroup $command)
    {
        $actor = $command->actor;

        $group = $this->groups->findOrFail($command->groupId, $actor);

        $this->assertCan($actor, 'delete', $group);

        $this->events->fire(
            new GroupWillBeDeleted($group, $actor, $command->data)
        );

        $group->delete();

        $this->dispatchEventsFor($group, $actor);

        return $group;
    }
}
