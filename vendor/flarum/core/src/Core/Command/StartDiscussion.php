<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Core\Command;

use Flarum\Core\User;

class StartDiscussion
{
    /**
     * The user authoring the discussion.
     *
     * @var User
     */
    public $actor;

    /**
     * The discussion attributes.
     *
     * @var array
     */
    public $data;

    /**
     * @param User $actor The user authoring the discussion.
     * @param array $data The discussion attributes.
     */
    public function __construct(User $actor, array $data, $ipAddress)
    {
        $this->actor = $actor;
        $this->data = $data;
        $this->ipAddress = $ipAddress;
    }
}
