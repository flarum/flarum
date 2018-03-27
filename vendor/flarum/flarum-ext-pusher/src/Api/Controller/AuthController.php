<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Pusher\Api\Controller;

use Flarum\Http\Controller\ControllerInterface;
use Flarum\Settings\SettingsRepositoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Pusher;
use Zend\Diactoros\Response\EmptyResponse;
use Zend\Diactoros\Response\JsonResponse;

class AuthController implements ControllerInterface
{
    /**
     * @var SettingsRepositoryInterface
     */
    protected $settings;

    /**
     * @param SettingsRepositoryInterface $settings
     */
    public function __construct(SettingsRepositoryInterface $settings)
    {
        $this->settings = $settings;
    }

    /**
     * @param ServerRequestInterface $request
     * @return EmptyResponse|JsonResponse
     */
    public function handle(ServerRequestInterface $request)
    {
        $userChannel = 'private-user'.$request->getAttribute('actor')->id;
        $body = $request->getParsedBody();

        if (array_get($body, 'channel_name') === $userChannel) {
            $pusher = new Pusher(
                $this->settings->get('flarum-pusher.app_key'),
                $this->settings->get('flarum-pusher.app_secret'),
                $this->settings->get('flarum-pusher.app_id'),
                ['cluster' => $this->settings->get('flarum-pusher.app_cluster')]
            );

            $payload = json_decode($pusher->socket_auth($userChannel, array_get($body, 'socket_id')), true);

            return new JsonResponse($payload);
        }

        return new EmptyResponse(403);
    }
}
