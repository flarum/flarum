<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Core\Post;

use Flarum\Core\Post;

/**
 * A post which indicates that a discussion's title was changed.
 *
 * The content is stored as a sequential array containing the old title and the
 * new title.
 */
class DiscussionRenamedPost extends AbstractEventPost implements MergeableInterface
{
    /**
     * {@inheritdoc}
     */
    public static $type = 'discussionRenamed';

    /**
     * {@inheritdoc}
     */
    public function saveAfter(Post $previous = null)
    {
        // If the previous post is another 'discussion renamed' post, and it's
        // by the same user, then we can merge this post into it. If we find
        // that we've in fact reverted the title, delete it. Otherwise, update
        // its content.
        if ($previous instanceof static && $this->user_id === $previous->user_id) {
            if ($previous->content[0] == $this->content[1]) {
                $previous->delete();
            } else {
                $previous->content = static::buildContent($previous->content[0], $this->content[1]);

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
     * @param string $oldTitle
     * @param string $newTitle
     * @return static
     */
    public static function reply($discussionId, $userId, $oldTitle, $newTitle)
    {
        $post = new static;

        $post->content = static::buildContent($oldTitle, $newTitle);
        $post->time = time();
        $post->discussion_id = $discussionId;
        $post->user_id = $userId;

        return $post;
    }

    /**
     * Build the content attribute.
     *
     * @param string $oldTitle The old title of the discussion.
     * @param string $newTitle The new title of the discussion.
     * @return array
     */
    protected static function buildContent($oldTitle, $newTitle)
    {
        return [$oldTitle, $newTitle];
    }
}
