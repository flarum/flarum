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
use Flarum\Event\AvatarWillBeDeleted;
use Illuminate\Contracts\Events\Dispatcher;
use League\Flysystem\FilesystemInterface;

class DeleteAvatarHandler
{
    use DispatchEventsTrait;
    use AssertPermissionTrait;

    /**
     * @var UserRepository
     */
    protected $users;

    /**
     * @var FilesystemInterface
     */
    protected $uploadDir;

    /**
     * @param Dispatcher $events
     * @param UserRepository $users
     * @param FilesystemInterface $uploadDir
     */
    public function __construct(Dispatcher $events, UserRepository $users, FilesystemInterface $uploadDir)
    {
        $this->events = $events;
        $this->users = $users;
        $this->uploadDir = $uploadDir;
    }

    /**
     * @param DeleteAvatar $command
     * @return \Flarum\Core\User
     * @throws PermissionDeniedException
     */
    public function handle(DeleteAvatar $command)
    {
        $actor = $command->actor;

        $user = $this->users->findOrFail($command->userId);

        if ($actor->id !== $user->id) {
            $this->assertCan($actor, 'edit', $user);
        }

        $avatarPath = $user->avatar_path;
        $user->changeAvatarPath(null);

        $this->events->fire(
            new AvatarWillBeDeleted($user, $actor)
        );

        $user->save();

        if ($this->uploadDir->has($avatarPath)) {
            $this->uploadDir->delete($avatarPath);
        }

        $this->dispatchEventsFor($user, $actor);

        return $user;
    }
}
