<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Sticky\Post;

use Flarum\Core\Post;
use Flarum\Core\Post\AbstractEventPost;
use Flarum\Core\Post\MergeableInterface;

class DiscussionStickiedPost extends AbstractEventPost implements MergeableInterface
{
    /**
     * {@inheritdoc}
     */
    public static $type = 'discussionStickied';

    /**
     * {@inheritdoc}
     */
    public function saveAfter(Post $previous = null)
    {
        // If the previous post is another 'discussion stickied' post, and it's
        // by the same user, then we can merge this post into it. If we find
        // that we've in fact reverted the sticky status, delete it. Otherwise,
        // update its content.
        if ($previous instanceof static && $this->user_id === $previous->user_id) {
            if ($previous->content['sticky'] != $this->content['sticky']) {
                $previous->delete();
            } else {
                $previous->content = $this->content;

                $previous->save();
            }

            return $previous;
        }

        $this->save();

        return $this;
    }

    /**
     * Create a new instance in reply to a discussion.
     *
     * @param int $discussionId
     * @param int $userId
     * @param bool $isSticky
     * @return static
     */
    public static function reply($discussionId, $userId, $isSticky)
    {
        $post = new static;

        $post->content = static::buildContent($isSticky);
        $post->time = time();
        $post->discussion_id = $discussionId;
        $post->user_id = $userId;

        return $post;
    }

    /**
     * Build the content attribute.
     *
     * @param bool $isSticky Whether or not the discussion is stickied.
     * @return array
     */
    public static function buildContent($isSticky)
    {
        return ['sticky' => (bool) $isSticky];
    }
}
