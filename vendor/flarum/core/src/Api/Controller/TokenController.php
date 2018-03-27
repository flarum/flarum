<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Api\Controller;

use Flarum\Core\Exception\PermissionDeniedException;
use Flarum\Core\Repository\UserRepository;
use Flarum\Http\AccessToken;
use Flarum\Http\Controller\ControllerInterface;
use Illuminate\Contracts\Bus\Dispatcher as BusDispatcher;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\JsonResponse;

class TokenController implements ControllerInterface
{
    /**
     * @var UserRepository
     */
    protected $users;

    /**
     * @var BusDispatcher
     */
    protected $bus;

    /**
     * @var EventDispatcher
     */
    protected $events;

    /**
     * @param UserRepository $users
     * @param BusDispatcher $bus
     * @param EventDispatcher $events
     */
    public function __construct(UserRepository $users, BusDispatcher $bus, EventDispatcher $events)
    {
        $this->users = $users;
        $this->bus = $bus;
        $this->events = $events;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(ServerRequestInterface $request)
    {
        $body = $request->getParsedBody();

        $identification = array_get($body, 'identification');
        $password = array_get($body, 'password');
        $lifetime = array_get($body, 'lifetime', 3600);

        $user = $this->users->findByIdentification($identification);

        if (! $user || ! $user->checkPassword($password)) {
            throw new PermissionDeniedException;
        }

        $token = AccessToken::generate($user->id, $lifetime);
        $token->save();

        return new JsonResponse([
            'token' => $token->id,
            'userId' => $user->id
        ]);
    }
}
