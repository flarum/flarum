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

use Flarum\Core\Command\RequestPasswordReset;
use Flarum\Core\Repository\UserRepository;
use Flarum\Http\Controller\ControllerInterface;
use Illuminate\Contracts\Bus\Dispatcher;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\EmptyResponse;

class ForgotPasswordController implements ControllerInterface
{
    /**
     * @var \Flarum\Core\Repository\UserRepository
     */
    protected $users;

    /**
     * @var Dispatcher
     */
    protected $bus;

    /**
     * @param \Flarum\Core\Repository\UserRepository $users
     * @param Dispatcher $bus
     */
    public function __construct(UserRepository $users, Dispatcher $bus)
    {
        $this->users = $users;
        $this->bus = $bus;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(ServerRequestInterface $request)
    {
        $email = array_get($request->getParsedBody(), 'email');

        $this->bus->dispatch(
            new RequestPasswordReset($email)
        );

        return new EmptyResponse;
    }
}
