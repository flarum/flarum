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

use Flarum\Api\Client;
use Flarum\Http\Controller\ControllerInterface;
use Flarum\Http\Rememberer;
use Flarum\Http\SessionAuthenticator;
use Psr\Http\Message\ServerRequestInterface as Request;
use Zend\Diactoros\Response\JsonResponse;

class RegisterController implements ControllerInterface
{
    /**
     * @var Client
     */
    protected $api;

    /**
     * @var SessionAuthenticator
     */
    protected $authenticator;

    /**
     * @var Rememberer
     */
    protected $rememberer;

    /**
     * @param Client $api
     * @param SessionAuthenticator $authenticator
     * @param Rememberer $rememberer
     */
    public function __construct(Client $api, SessionAuthenticator $authenticator, Rememberer $rememberer)
    {
        $this->api = $api;
        $this->authenticator = $authenticator;
        $this->rememberer = $rememberer;
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function handle(Request $request)
    {
        $controller = 'Flarum\Api\Controller\CreateUserController';
        $actor = $request->getAttribute('actor');
        $body = ['data' => ['attributes' => $request->getParsedBody()]];

        $response = $this->api->send($controller, $actor, [], $body);

        $body = json_decode($response->getBody());

        if (isset($body->data)) {
            $userId = $body->data->id;

            $session = $request->getAttribute('session');
            $this->authenticator->logIn($session, $userId);

            $response = $this->rememberer->rememberUser($response, $userId);
        }

        return $response;
    }
}
