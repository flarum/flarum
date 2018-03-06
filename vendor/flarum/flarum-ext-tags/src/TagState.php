<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Tags;

use Flarum\Core\Support\EventGeneratorTrait;
use Flarum\Core\User;
use Flarum\Database\AbstractModel;
use Illuminate\Database\Eloquent\Builder;

/**
 * @property int $user_id
 * @property int $tag_id
 * @property \Carbon\Carbon|null $read_time
 * @property bool $is_hidden
 * @property Tag $tag
 * @property User $user
 */
class TagState extends AbstractModel
{
    use EventGeneratorTrait;

    /**
     * {@inheritdoc}
     */
    protected $table = 'users_tags';

    /**
     * {@inheritdoc}
     */
    protected $dates = ['read_time'];

    /**
     * Define the relationship with the tag that this state is for.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function tag()
    {
        return $this->belongsTo(Tag::class, 'tag_id');
    }

    /**
     * Define the relationship with the user that this state is for.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Set the keys for a save update query.
     *
     * @param Builder $query
     * @return Builder
     */
    protected function setKeysForSaveQuery(Builder $query)
    {
        $query->where('tag_id', $this->tag_id)
              ->where('user_id', $this->user_id);

        return $query;
    }
}
