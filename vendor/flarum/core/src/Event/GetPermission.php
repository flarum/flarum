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

class GetPermission
{
    /**
     * @var User
     */
    public $actor;

    /**
     * @var string
     */
    public $ability;

    /**
     * @var mixed
     */
    public $model;

    /**
     * @param User $actor
     * @param string $ability
     * @param mixed $model
     */
    public function __construct(User $actor, $ability, $model)
    {
        $this->actor = $actor;
        $this->ability = $ability;
        $this->model = $model;
    }
}
