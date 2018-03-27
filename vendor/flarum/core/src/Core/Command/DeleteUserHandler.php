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
use Flarum\Core\Repository\UserRepository;
use Flarum\Core\Support\DispatchEventsTrait;
use Flarum\Event\UserWillBeDeleted;
use Illuminate\Contracts\Events\Dispatcher;

class DeleteUserHandler
{
    use DispatchEventsTrait;
    use AssertPermissionTrait;

    /**
     * @var UserRepository
     */
    protected $users;

    /**
     * @param Dispatcher $events
     * @param UserRepository $users
     */
    public function __construct(Dispatcher $events, UserRepository $users)
    {
        $this->events = $events;
        $this->users = $users;
    }

    /**
     * @param DeleteUser $command
     * @return \Flarum\Core\User
     * @throws PermissionDeniedException
     */
    public function handle(DeleteUser $command)
    {
        $actor = $command->actor;
        $user = $this->users->findOrFail($command->userId, $actor);

        $this->assertCan($actor, 'delete', $user);

        $this->events->fire(
            new UserWillBeDeleted($user, $actor, $command->data)
        );

        $user->delete();

        $this->dispatchEventsFor($user, $actor);

        return $user;
    }
}
