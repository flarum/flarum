<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Http;

use Flarum\Database\AbstractModel;

/**
 * @property string $id
 * @property int $user_id
 * @property int $last_activity
 * @property int $lifetime
 * @property \Flarum\Core\User|null $user
 */
class AccessToken extends AbstractModel
{
    /**
     * {@inheritdoc}
     */
    protected $table = 'access_tokens';

    /**
     * Use a custom primary key for this model.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * Generate an access token for the specified user.
     *
     * @param int $userId
     * @param int $lifetime
     * @return static
     */
    public static function generate($userId, $lifetime = 3600)
    {
        $token = new static;

        $token->id = str_random(40);
        $token->user_id = $userId;
        $token->last_activity = time();
        $token->lifetime = $lifetime;

        return $token;
    }

    public function touch()
    {
        $this->last_activity = time();

        return $this->save();
    }

    /**
     * Define the relationship with the owner of this access token.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo('Flarum\Core\User');
    }
}
