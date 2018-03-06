<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Lock\Notification;

use Flarum\Core\Discussion;
use Flarum\Core\Notification\BlueprintInterface;
use Flarum\Lock\Post\DiscussionLockedPost;

class DiscussionLockedBlueprint implements BlueprintInterface
{
    /**
     * @var DiscussionLockedPost
     */
    protected $post;

    /**
     * @param DiscussionLockedPost $post
     */
    public function __construct(DiscussionLockedPost $post)
    {
        $this->post = $post;
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
    public function getSubject()
    {
        return $this->post->discussion;
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
    public static function getType()
    {
        return 'discussionLocked';
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubjectModel()
    {
        return Discussion::class;
    }
}
