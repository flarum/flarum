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

use DateTime;
use Flarum\Core\Exception\InvalidConfirmationTokenException;
use Flarum\Database\AbstractModel;

/**
 * @todo document database columns with @property
 */
class AuthToken extends AbstractModel
{
    /**
     * {@inheritdoc}
     */
    protected $table = 'auth_tokens';

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
     * Generate an email token for the specified user.
     *
     * @param string $email
     *
     * @return static
     */
    public static function generate($payload)
    {
        $token = new static;

        $token->id = str_random(40);
        $token->payload = $payload;
        $token->created_at = time();

        return $token;
    }

    /**
     * Unserialize the payload attribute from the database's JSON value.
     *
     * @param string $value
     * @return string
     */
    public function getPayloadAttribute($value)
    {
        return json_decode($value, true);
    }

    /**
     * Serialize the payload attribute to be stored in the database as JSON.
     *
     * @param string $value
     */
    public function setPayloadAttribute($value)
    {
        $this->attributes['payload'] = json_encode($value);
    }

    /**
     * Find the token with the given ID, and assert that it has not expired.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $id
     *
     * @throws InvalidConfirmationTokenException
     *
     * @return static
     */
    public function scopeValidOrFail($query, $id)
    {
        $token = $query->find($id);

        if (! $token || $token->created_at < new DateTime('-1 day')) {
            throw new InvalidConfirmationTokenException;
        }

        return $token;
    }
}
