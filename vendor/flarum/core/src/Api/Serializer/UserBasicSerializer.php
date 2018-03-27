<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Api\Serializer;

use Flarum\Core\User;
use InvalidArgumentException;

class UserBasicSerializer extends AbstractSerializer
{
    /**
     * {@inheritdoc}
     */
    protected $type = 'users';

    /**
     * {@inheritdoc}
     *
     * @param User $user
     * @throws InvalidArgumentException
     */
    protected function getDefaultAttributes($user)
    {
        if (! ($user instanceof User)) {
            throw new InvalidArgumentException(
                get_class($this).' can only serialize instances of '.User::class
            );
        }

        return [
            'username'  => $user->username,
            'avatarUrl' => $user->avatar_url
        ];
    }

    /**
     * @return \Tobscure\JsonApi\Relationship
     */
    protected function groups($user)
    {
        return $this->hasMany($user, 'Flarum\Api\Serializer\GroupSerializer');
    }
}
