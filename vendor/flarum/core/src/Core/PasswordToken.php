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

use Flarum\Database\AbstractModel;

/**
 * @todo document database columns with @property
 */
class PasswordToken extends AbstractModel
{
    /**
     * {@inheritdoc}
     */
    protected $table = 'password_tokens';

    /**
     * {@inheritdoc}
     */
    protected $dates = ['created_at'];

    /**
     * Use a custom primary key for this model.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * Generate a password token for the specified user.
     *
     * @param int $userId
     * @return static
     */
    public static function generate($userId)
    {
        $token = new static;

        $token->id = str_random(40);
        $token->user_id = $userId;
        $token->created_at = time();

        return $token;
    }

    /**
     * Define the relationship with the owner of this password token.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo('Flarum\Core\User');
    }
}
