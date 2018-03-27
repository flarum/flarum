<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Tags\Post;

use Flarum\Core\Post;
use Flarum\Core\Post\AbstractEventPost;
use Flarum\Core\Post\MergeableInterface;

class DiscussionTaggedPost extends AbstractEventPost implements MergeableInterface
{
    /**
     * {@inheritdoc}
     */
    public static $type = 'discussionTagged';

    /**
     * {@inheritdoc}
     */
    public function saveAfter(Post $previous = null)
    {
        // If the previous post is another 'discussion tagged' post, and it's
        // by the same user, then we can merge this post into it. If we find
        // that we've in fact reverted the tag changes, delete it. Otherwise,
        // update its content.
        if ($previous instanceof static && $this->user_id === $previous->user_id) {
            if ($previous->content[0] == $this->content[1]) {
                $previous->delete();
            } else {
                $previous->content = static::buildContent($previous->content[0], $this->content[1]);
                $previous->time = $this->time;

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
     * @param array $oldTagIds
     * @param array $newTagIds
     * @return static
     */
    public static function reply($discussionId, $userId, array $oldTagIds, array $newTagIds)
    {
        $post = new static;

        $post->content = static::buildContent($oldTagIds, $newTagIds);
        $post->time = time();
        $post->discussion_id = $discussionId;
        $post->user_id = $userId;

        return $post;
    }

    /**
     * Build the content attribute.
     *
     * @param array $oldTagIds
     * @param array $newTagIds
     * @return array
     */
    public static function buildContent(array $oldTagIds, array $newTagIds)
    {
        return [array_map('intval', $oldTagIds), array_map('intval', $newTagIds)];
    }
}
