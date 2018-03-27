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
use Flarum\Core\User;
use Flarum\Event\PostWasHidden;
use Flarum\Event\PostWasPosted;
use Flarum\Event\PostWasRestored;
use Flarum\Event\PostWasRevised;
use Flarum\Formatter\Formatter;

/**
 * A standard comment in a discussion.
 *
 * @property string $parsed_content
 * @property string $content_html
 */
class CommentPost extends Post
{
    /**
     * {@inheritdoc}
     */
    public static $type = 'comment';

    /**
     * The text formatter instance.
     *
     * @var \Flarum\Formatter\Formatter
     */
    protected static $formatter;

    /**
     * Create a new instance in reply to a discussion.
     *
     * @param int $discussionId
     * @param string $content
     * @param int $userId
     * @param string $ipAddress
     * @return static
     */
    public static function reply($discussionId, $content, $userId, $ipAddress)
    {
        $post = new static;

        $post->time = time();
        $post->discussion_id = $discussionId;
        $post->user_id = $userId;
        $post->type = static::$type;
        $post->ip_address = $ipAddress;

        // Set content last, as the parsing may rely on other post attributes.
        $post->content = $content;

        $post->raise(new PostWasPosted($post));

        return $post;
    }

    /**
     * Revise the post's content.
     *
     * @param string $content
     * @param User $actor
     * @return $this
     */
    public function revise($content, User $actor)
    {
        if ($this->content !== $content) {
            $this->content = $content;

            $this->edit_time = time();
            $this->edit_user_id = $actor->id;

            $this->raise(new PostWasRevised($this));
        }

        return $this;
    }

    /**
     * Hide the post.
     *
     * @param User $actor
     * @return $this
     */
    public function hide(User $actor = null)
    {
        if (! $this->hide_time) {
            $this->hide_time = time();
            $this->hide_user_id = $actor ? $actor->id : null;

            $this->raise(new PostWasHidden($this));
        }

        return $this;
    }

    /**
     * Restore the post.
     *
     * @return $this
     */
    public function restore()
    {
        if ($this->hide_time !== null) {
            $this->hide_time = null;
            $this->hide_user_id = null;

            $this->raise(new PostWasRestored($this));
        }

        return $this;
    }

    /**
     * Unparse the parsed content.
     *
     * @param string $value
     * @return string
     */
    public function getContentAttribute($value)
    {
        return static::$formatter->unparse($value);
    }

    /**
     * Get the parsed/raw content.
     *
     * @return string
     */
    public function getParsedContentAttribute()
    {
        return $this->attributes['content'];
    }

    /**
     * Parse the content before it is saved to the database.
     *
     * @param string $value
     */
    public function setContentAttribute($value)
    {
        $this->attributes['content'] = $value ? static::$formatter->parse($value, $this) : null;
    }

    /**
     * Get the content rendered as HTML.
     *
     * @param string $value
     * @return string
     */
    public function getContentHtmlAttribute($value)
    {
        return static::$formatter->render($this->attributes['content'], $this);
    }

    /**
     * Get the text formatter instance.
     *
     * @return \Flarum\Formatter\Formatter
     */
    public static function getFormatter()
    {
        return static::$formatter;
    }

    /**
     * Set the text formatter instance.
     *
     * @param \Flarum\Formatter\Formatter $formatter
     */
    public static function setFormatter(Formatter $formatter)
    {
        static::$formatter = $formatter;
    }
}
