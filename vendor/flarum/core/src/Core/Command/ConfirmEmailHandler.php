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

use Flarum\Core\EmailToken;
use Flarum\Core\Repository\UserRepository;
use Flarum\Core\Support\DispatchEventsTrait;
use Illuminate\Contracts\Events\Dispatcher;

class ConfirmEmailHandler
{
    use DispatchEventsTrait;

    /**
     * @var UserRepository
     */
    protected $users;

    /**
     * @param UserRepository $users
     */
    public function __construct(Dispatcher $events, UserRepository $users)
    {
        $this->events = $events;
        $this->users = $users;
    }

    /**
     * @param ConfirmEmail $command
     * @return \Flarum\Core\User
     */
    public function handle(ConfirmEmail $command)
    {
        /** @var EmailToken $token */
        $token = EmailToken::validOrFail($command->token);

        $user = $token->user;
        $user->changeEmail($token->email);

        if (! $user->is_activated) {
            $user->activate();
        }

        $user->save();
        $this->dispatchEventsFor($user);

        $token->delete();

        return $user;
    }
}
