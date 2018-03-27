<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Core;

use Flarum\Core\Post\MergeableInterface;
use Flarum\Core\Support\EventGeneratorTrait;
use Flarum\Core\Support\ScopeVisibilityTrait;
use Flarum\Database\AbstractModel;
use Flarum\Event\DiscussionWasDeleted;
use Flarum\Event\DiscussionWasHidden;
use Flarum\Event\DiscussionWasRenamed;
use Flarum\Event\DiscussionWasRestored;
use Flarum\Event\DiscussionWasStarted;
use Flarum\Event\PostWasDeleted;
use Flarum\Event\ScopePostVisibility;
use Flarum\Util\Str;

/**
 * @property int $id
 * @property string $title
 * @property string $slug
 * @property int $comments_count
 * @property int $participants_count
 * @property int $number_index
 * @property \Carbon\Carbon $start_time
 * @property int|null $start_user_id
 * @property int|null $start_post_id
 * @property \Carbon\Carbon|null $last_time
 * @property int|null $last_user_id
 * @property int|null $last_post_id
 * @property int|null $last_post_number
 * @property \Carbon\Carbon|null $hide_time
 * @property int|null $hide_user_id
 * @property DiscussionState|null $state
 * @property \Illuminate\Database\Eloquent\Collection $posts
 * @property \Illuminate\Database\Eloquent\Collection $comments
 * @property \Illuminate\Database\Eloquent\Collection $participants
 * @property Post|null $startPost
 * @property User|null $startUser
 * @property Post|null $lastPost
 * @property User|null $lastUser
 * @property \Illuminate\Database\Eloquent\Collection $readers
 * @property bool $is_private
 */
class Discussion extends AbstractModel
{
    use EventGeneratorTrait;
    use ScopeVisibilityTrait;

    /**
     * {@inheritdoc}
     */
    protected $table = 'discussions';

    /**
     * An array of posts that have been modified during this request.
     *
     * @var array
     */
    protected $modifiedPosts = [];

    /**
     * {@inheritdoc}
     */
    protected $dates = ['start_time', 'last_time', 'hide_time'];

    /**
     * Casts properties to a specific type.
     *
     * @var array
     */
    protected $casts = [
        'is_private' => 'boolean'
    ];

    /**
     * The user for which the state relationship should be loaded.
     *
     * @var User
     */
    protected static $stateUser;

    /**
     * Boot the model.
     *
     * @return void
     */
    public static function boot()
    {
        parent::boot();

        static::deleted(function ($discussion) {
            $discussion->raise(new DiscussionWasDeleted($discussion));

            // Delete all of the posts in the discussion. Before we delete them
            // in a big batch query, we will loop through them and raise a
            // PostWasDeleted event for each post.
            $posts = $discussion->posts()->allTypes();

            foreach ($posts->get() as $post) {
                $discussion->raise(new PostWasDeleted($post));
            }

            $posts->delete();

            // Delete all of the 'state' records for all of the users who have
            // read the discussion.
            $discussion->readers()->detach();
        });
    }

    /**
     * Start a new discussion. Raises the DiscussionWasStarted event.
     *
     * @param string $title
     * @param User $user
     * @return static
     */
    public static function start($title, User $user)
    {
        $discussion = new static;

        $discussion->title = $title;
        $discussion->start_time = time();
        $discussion->start_user_id = $user->id;

        $discussion->setRelation('startUser', $user);

        $discussion->raise(new DiscussionWasStarted($discussion));

        return $discussion;
    }

    /**
     * Rename the discussion. Raises the DiscussionWasRenamed event.
     *
     * @param string $title
     * @return $this
     */
    public function rename($title)
    {
        if ($this->title !== $title) {
            $oldTitle = $this->title;
            $this->title = $title;

            $this->raise(new DiscussionWasRenamed($this, $oldTitle));
        }

        return $this;
    }

    /**
     * Hide the discussion.
     *
     * @param User $actor
     * @return $this
     */
    public function hide(User $actor = null)
    {
        if (! $this->hide_time) {
            $this->hide_time = time();
            $this->hide_user_id = $actor ? $actor->id : null;

            $this->raise(new DiscussionWasHidden($this));
        }

        return $this;
    }

    /**
     * Restore the discussion.
     *
     * @return $this
     */
    public function restore()
    {
        if ($this->hide_time !== null) {
            $this->hide_time = null;
            $this->hide_user_id = null;

            $this->raise(new DiscussionWasRestored($this));
        }

        return $this;
    }

    /**
     * Set the discussion's start post details.
     *
     * @param Post $post
     * @return $this
     */
    public function setStartPost(Post $post)
    {
        $this->start_time = $post->time;
        $this->start_user_id = $post->user_id;
        $this->start_post_id = $post->id;

        return $this;
    }

    /**
     * Set the discussion's last post details.
     *
     * @param Post $post
     * @return $this
     */
    public function setLastPost(Post $post)
    {
        $this->last_time = $post->time;
        $this->last_user_id = $post->user_id;
        $this->last_post_id = $post->id;
        $this->last_post_number = $post->number;

        return $this;
    }

    /**
     * Refresh a discussion's last post details.
     *
     * @return $this
     */
    public function refreshLastPost()
    {
        if ($lastPost = $this->comments()->latest('time')->first()) {
            $this->setLastPost($lastPost);
        }

        return $this;
    }

    /**
     * Refresh the discussion's comments count.
     *
     * @return $this
     */
    public function refreshCommentsCount()
    {
        $this->comments_count = $this->comments()->count();

        return $this;
    }

    /**
     * Refresh the discussion's participants count.
     *
     * @return $this
     */
    public function refreshParticipantsCount()
    {
        $this->participants_count = $this->participants()->count('users.id');

        return $this;
    }

    /**
     * Save a post, attempting to merge it with the discussion's last post.
     *
     * The merge logic is delegated to the new post. (As an example, a
     * DiscussionRenamedPost will merge if adjacent to another
     * DiscussionRenamedPost, and delete if the title has been reverted
     * completely.)
     *
     * @param MergeableInterface $post The post to save.
     * @return Post The resulting post. It may or may not be the same post as
     *     was originally intended to be saved. It also may not exist, if the
     *     merge logic resulted in deletion.
     */
    public function mergePost(MergeableInterface $post)
    {
        $lastPost = $this->posts()->latest('time')->first();

        $post = $post->saveAfter($lastPost);

        return $this->modifiedPosts[] = $post;
    }

    /**
     * Get the posts that have been modified during this request.
     *
     * @return array
     */
    public function getModifiedPosts()
    {
        return $this->modifiedPosts;
    }

    /**
     * Define the relationship with the discussion's posts.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function posts()
    {
        return $this->hasMany('Flarum\Core\Post');
    }

    /**
     * Define the relationship with the discussion's posts, but only ones which
     * are visible to the given user.
     *
     * @param User $user
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function postsVisibleTo(User $user)
    {
        $relation = $this->posts();

        static::$dispatcher->fire(
            new ScopePostVisibility($this, $relation->getQuery(), $user)
        );

        return $relation;
    }

    /**
     * Define the relationship with the discussion's publicly-visible comments.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function comments()
    {
        return $this->postsVisibleTo(new Guest)->where('type', 'comment');
    }

    /**
     * Query the discussion's participants (a list of unique users who have
     * posted in the discussion).
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function participants()
    {
        return User::join('posts', 'posts.user_id', '=', 'users.id')
            ->where('posts.discussion_id', $this->id)
            ->select('users.*')
            ->distinct();
    }

    /**
     * Define the relationship with the discussion's first post.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function startPost()
    {
        return $this->belongsTo('Flarum\Core\Post', 'start_post_id');
    }

    /**
     * Define the relationship with the discussion's author.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function startUser()
    {
        return $this->belongsTo('Flarum\Core\User', 'start_user_id');
    }

    /**
     * Define the relationship with the discussion's last post.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function lastPost()
    {
        return $this->belongsTo('Flarum\Core\Post', 'last_post_id');
    }

    /**
     * Define the relationship with the discussion's most recent author.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function lastUser()
    {
        return $this->belongsTo('Flarum\Core\User', 'last_user_id');
    }

    /**
     * Define the relationship with the discussion's readers.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function readers()
    {
        return $this->belongsToMany('Flarum\Core\User', 'users_discussions');
    }

    /**
     * Define the relationship with the discussion's state for a particular
     * user.
     *
     * If no user is passed (i.e. in the case of eager loading the 'state'
     * relation), then the static `$stateUser` property is used.
     *
     * @see Discussion::setStateUser()
     *
     * @param User|null $user
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function state(User $user = null)
    {
        $user = $user ?: static::$stateUser;

        return $this->hasOne('Flarum\Core\DiscussionState')->where('user_id', $user ? $user->id : null);
    }

    /**
     * Get the state model for a user, or instantiate a new one if it does not
     * exist.
     *
     * @param User $user
     * @return \Flarum\Core\DiscussionState
     */
    public function stateFor(User $user)
    {
        $state = $this->state($user)->first();

        if (! $state) {
            $state = new DiscussionState;
            $state->discussion_id = $this->id;
            $state->user_id = $user->id;
        }

        return $state;
    }

    /**
     * Set the user for which the state relationship should be loaded.
     *
     * @param User $user
     */
    public static function setStateUser(User $user)
    {
        static::$stateUser = $user;
    }

    /**
     * Set the discussion title.
     *
     * This automatically creates a matching slug for the discussion.
     *
     * @param string $title
     */
    protected function setTitleAttribute($title)
    {
        $this->attributes['title'] = $title;
        $this->slug = Str::slug($title);
    }
}
