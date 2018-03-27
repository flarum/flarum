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

use Flarum\Core\Guest;
use Flarum\Event\NotificationWillBeSent;
use Flarum\Event\PostWasPosted;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Events\Dispatcher;
use Pusher;

class PushNewPosts
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
        $events->listen(PostWasPosted::class, [$this, 'pushNewPost']);
        $events->listen(NotificationWillBeSent::class, [$this, 'pushNotification']);
    }

    /**
     * @param PostWasPosted $event
     */
    public function pushNewPost(PostWasPosted $event)
    {
        if ($event->post->isVisibleTo(new Guest)) {
            $pusher = $this->getPusher();

            $pusher->trigger('public', 'newPost', [
                'postId' => $event->post->id,
                'discussionId' => $event->post->discussion->id,
                'tagIds' => $event->post->discussion->tags()->lists('id')
            ]);
        }
    }

    /**
     * @param NotificationWillBeSent $event
     */
    public function pushNotification(NotificationWillBeSent $event)
    {
        $pusher = $this->getPusher();
        $blueprint = $event->blueprint;

        foreach ($event->users as $user) {
            if ($user->shouldAlert($blueprint::getType())) {
                $pusher->trigger('private-user'.$user->id, 'notification', null);
            }
        }
    }

    /**
     * @return Pusher
     */
    protected function getPusher()
    {
        return new Pusher(
            $this->settings->get('flarum-pusher.app_key'),
            $this->settings->get('flarum-pusher.app_secret'),
            $this->settings->get('flarum-pusher.app_id')
        );
    }
}
