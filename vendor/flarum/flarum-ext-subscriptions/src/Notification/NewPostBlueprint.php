<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Subscriptions\Notification;

use Flarum\Core\Discussion;
use Flarum\Core\Notification\BlueprintInterface;
use Flarum\Core\Notification\MailableInterface;
use Flarum\Core\Post;

class NewPostBlueprint implements BlueprintInterface, MailableInterface
{
    /**
     * @var Post
     */
    public $post;

    /**
     * @param Post $post
     */
    public function __construct(Post $post)
    {
        $this->post = $post;
    }

    /**
     * {@inheritdoc}
     */
    public function getSubject()
    {
        return $this->post->discussion;
    }

    /**
     * {@inheritdoc}
     */
    public function getSender()
    {
        return $this->post->user;
    }

    /**
     * {@inheritdoc}
     */
    public function getData()
    {
        return ['postNumber' => (int) $this->post->number];
    }

    /**
     * {@inheritdoc}
     */
    public function getEmailView()
    {
        return ['text' => 'flarum-subscriptions::emails.newPost'];
    }

    /**
     * {@inheritdoc}
     */
    public function getEmailSubject()
    {
        return '[New Post] '.$this->post->discussion->title;
    }

    /**
     * {@inheritdoc}
     */
    public static function getType()
    {
        return 'newPost';
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubjectModel()
    {
        return Discussion::class;
    }
}
