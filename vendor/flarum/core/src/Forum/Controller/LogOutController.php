<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Forum\Controller;

use Flarum\Core\Access\AssertPermissionTrait;
use Flarum\Event\UserLoggedOut;
use Flarum\Foundation\Application;
use Flarum\Http\Controller\ControllerInterface;
use Flarum\Http\Exception\TokenMismatchException;
use Flarum\Http\Rememberer;
use Flarum\Http\SessionAuthenticator;
use Illuminate\Contracts\Events\Dispatcher;
use Psr\Http\Message\ServerRequestInterface as Request;
use Zend\Diactoros\Response\RedirectResponse;

class LogOutController implements ControllerInterface
{
    use AssertPermissionTrait;

    /**
     * @var Application
     */
    protected $app;

    /**
     * @var Dispatcher
     */
    protected $events;

    /**
     * @var SessionAuthenticator
     */
    protected $authenticator;

    /**
     * @var Rememberer
     */
    protected $rememberer;

    /**
     * @param Application $app
     * @param Dispatcher $events
     * @param SessionAuthenticator $authenticator
     * @param Rememberer $rememberer
     */
    public function __construct(Application $app, Dispatcher $events, SessionAuthenticator $authenticator, Rememberer $rememberer)
    {
        $this->app = $app;
        $this->events = $events;
        $this->authenticator = $authenticator;
        $this->rememberer = $rememberer;
    }

    /**
     * @param Request $request
     * @return \Psr\Http\Message\ResponseInterface
     * @throws TokenMismatchException
     */
    public function handle(Request $request)
    {
        $session = $request->getAttribute('session');

        if (array_get($request->getQueryParams(), 'token') !== $session->get('csrf_token')) {
            throw new TokenMismatchException;
        }

        $actor = $request->getAttribute('actor');

        $this->assertRegistered($actor);

        $url = array_get($request->getQueryParams(), 'return', $this->app->url());

        $response = new RedirectResponse($url);

        $this->authenticator->logOut($session);

        $actor->accessTokens()->delete();

        $this->events->fire(new UserLoggedOut($actor));

        return $this->rememberer->forget($response);
    }
}
