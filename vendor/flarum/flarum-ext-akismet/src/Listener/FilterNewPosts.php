<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Akismet\Listener;

use Flarum\Approval\Event\PostWasApproved;
use Flarum\Event\PostWasHidden;
use Flarum\Event\PostWillBeSaved;
use Flarum\Flags\Flag;
use Flarum\Foundation\Application;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Events\Dispatcher;
use TijsVerkoyen\Akismet\Akismet;

class FilterNewPosts
{
    /**
     * @var SettingsRepositoryInterface
     */
    protected $settings;

    /**
     * @var Application
     */
    protected $app;

    /**
     * @param SettingsRepositoryInterface $settings
     * @param Application $app
     */
    public function __construct(SettingsRepositoryInterface $settings, Application $app)
    {
        $this->settings = $settings;
        $this->app = $app;
    }

    /**
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events)
    {
        $events->listen(PostWillBeSaved::class, [$this, 'validatePost']);
        $events->listen(PostWasApproved::class, [$this, 'submitHam']);
        $events->listen(PostWasHidden::class, [$this, 'submitSpam']);
    }

    /**
     * @param PostWillBeSaved $event
     */
    public function validatePost(PostWillBeSaved $event)
    {
        $post = $event->post;

        if ($post->exists || $post->user->groups()->count()) {
            return;
        }

        $isSpam = $this->getAkismet()->isSpam(
            $post->content,
            $post->user->username,
            $post->user->email,
            null,
            'comment'
        );

        if ($isSpam) {
            $post->is_approved = false;
            $post->is_spam = true;

            $post->afterSave(function ($post) {
                if ($post->number == 1) {
                    $post->discussion->is_approved = false;
                    $post->discussion->save();
                }

                $flag = new Flag;

                $flag->post_id = $post->id;
                $flag->type = 'akismet';
                $flag->time = time();

                $flag->save();
            });
        }
    }

    /**
     * @param PostWasApproved $event
     */
    public function submitHam(PostWasApproved $event)
    {
        $post = $event->post;

        if ($post->is_spam) {
            $this->getAkismet()->submitHam(
                $post->ip_address,
                null,
                $post->content,
                $post->user->username,
                $post->user->email
            );
        }
    }

    /**
     * @param PostWasHidden $event
     */
    public function submitSpam(PostWasHidden $event)
    {
        $post = $event->post;

        if ($post->is_spam) {
            $this->getAkismet()->submitSpam(
                $post->ip_address,
                null,
                $post->content,
                $post->user->username,
                $post->user->email
            );
        }
    }

    /**
     * @return Akismet
     */
    protected function getAkismet()
    {
        return new Akismet(
            $this->settings->get('flarum-akismet.api_key'),
            $this->app->url()
        );
    }
}
