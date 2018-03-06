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

use Flarum\Core\Support\EventGeneratorTrait;
use Flarum\Database\AbstractModel;
use Flarum\Event\DiscussionWasRead;
use Illuminate\Database\Eloquent\Builder;

/**
 * Models a discussion-user state record in the database.
 *
 * Stores information about how much of a discussion a user has read. Can also
 * be used to store other information, if the appropriate columns are added to
 * the database, like a user's subscription status for a discussion.
 *
 * @property int $user_id
 * @property int $discussion_id
 * @property \Carbon\Carbon|null $read_time
 * @property int|null $read_number
 * @property Discussion $discussion
 * @property \Flarum\Core\User $user
 */
class DiscussionState extends AbstractModel
{
    use EventGeneratorTrait;

    /**
     * {@inheritdoc}
     */
    protected $table = 'users_discussions';

    /**
     * {@inheritdoc}
     */
    protected $dates = ['read_time'];

    /**
     * Mark the discussion as being read up to a certain point. Raises the
     * DiscussionWasRead event.
     *
     * @param int $number
     * @return $this
     */
    public function read($number)
    {
        if ($number > $this->read_number) {
            $this->read_number = $number;
            $this->read_time = time();

            $this->raise(new DiscussionWasRead($this));
        }

        return $this;
    }

    /**
     * Define the relationship with the discussion that this state is for.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function discussion()
    {
        return $this->belongsTo('Flarum\Core\Discussion', 'discussion_id');
    }

    /**
     * Define the relationship with the user that this state is for.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo('Flarum\Core\User', 'user_id');
    }

    /**
     * Set the keys for a save update query.
     *
     * @param Builder $query
     * @return Builder
     */
    protected function setKeysForSaveQuery(Builder $query)
    {
        $query->where('discussion_id', $this->discussion_id)
              ->where('user_id', $this->user_id);

        return $query;
    }
}
