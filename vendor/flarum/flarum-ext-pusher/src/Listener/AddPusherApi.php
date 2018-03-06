<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Pusher\Listener;

use Flarum\Api\Serializer\ForumSerializer;
use Flarum\Event\ConfigureApiRoutes;
use Flarum\Event\PrepareApiAttributes;
use Flarum\Pusher\Api\Controller\AuthController;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Events\Dispatcher;

class AddPusherApi
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
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events)
    {
        $events->listen(PrepareApiAttributes::class, [$this, 'addAttributes']);
        $events->listen(ConfigureApiRoutes::class, [$this, 'addRoutes']);
    }

    /**
     * @param PrepareApiAttributes $event
     */
    public function addAttributes(PrepareApiAttributes $event)
    {
        if ($event->isSerializer(ForumSerializer::class)) {
            $event->attributes['pusherKey'] = $this->settings->get('flarum-pusher.app_key');
            $event->attributes['pusherCluster'] = $this->settings->get('flarum-pusher.app_cluster');
        }
    }

    /**
     * @param ConfigureApiRoutes $event
     */
    public function addRoutes(ConfigureApiRoutes $event)
    {
        $event->post('/pusher/auth', 'pusher.auth', AuthController::class);
    }
}
