<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Event;

use Flarum\Core\User;

class CheckUserPassword
{
    /**
     * @var User
     */
    public $user;

    /**
     * @var string
     */
    public $password;

    /**
     * @param User $user
     * @param string $password
     */
    public function __construct($user, $password)
    {
        $this->user = $user;
        $this->password = $password;
    }
}
