<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Core\Notification;

use Flarum\Core\Post\DiscussionRenamedPost;

class DiscussionRenamedBlueprint implements BlueprintInterface
{
    /**
     * @var DiscussionRenamedPost
     */
    protected $post;

    /**
     * @param DiscussionRenamedPost $post
     */
    public function __construct(DiscussionRenamedPost $post)
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
        return 'discussionRenamed';
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubjectModel()
    {
        return 'Flarum\Core\Discussion';
    }
}
