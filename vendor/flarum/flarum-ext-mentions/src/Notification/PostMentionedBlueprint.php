<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Mentions\Notification;

use Flarum\Core\Notification\BlueprintInterface;
use Flarum\Core\Notification\MailableInterface;
use Flarum\Core\Post;

class PostMentionedBlueprint implements BlueprintInterface, MailableInterface
{
    /**
     * @var Post
     */
    public $post;

    /**
     * @var Post
     */
    public $reply;

    /**
     * @param Post $post
     * @param Post $reply
     */
    public function __construct(Post $post, Post $reply)
    {
        $this->post = $post;
        $this->reply = $reply;
    }

    /**
     * {@inheritdoc}
     */
    public function getSubject()
    {
        return $this->post;
    }

    /**
     * {@inheritdoc}
     */
    public function getSender()
    {
        return $this->reply->user;
    }

    /**
     * {@inheritdoc}
     */
    public function getData()
    {
        return ['replyNumber' => (int) $this->reply->number];
    }

    /**
     * {@inheritdoc}
     */
    public function getEmailView()
    {
        return ['text' => 'flarum-mentions::emails.postMentioned'];
    }

    /**
     * {@inheritdoc}
     */
    public function getEmailSubject()
    {
        return "{$this->reply->user->username} replied to your post in {$this->post->discussion->title}";
    }

    /**
     * {@inheritdoc}
     */
    public static function getType()
    {
        return 'postMentioned';
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubjectModel()
    {
        return Post::class;
    }
}
