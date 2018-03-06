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

class UserMentionedBlueprint implements BlueprintInterface, MailableInterface
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
        return $this->post;
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
    }

    /**
     * {@inheritdoc}
     */
    public function getEmailView()
    {
        return ['text' => 'flarum-mentions::emails.userMentioned'];
    }

    /**
     * {@inheritdoc}
     */
    public function getEmailSubject()
    {
        return "{$this->post->user->username} mentioned you in {$this->post->discussion->title}";
    }

    /**
     * {@inheritdoc}
     */
    public static function getType()
    {
        return 'userMentioned';
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubjectModel()
    {
        return Post::class;
    }
}
