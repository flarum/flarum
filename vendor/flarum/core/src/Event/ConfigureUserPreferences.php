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

class ConfigureUserPreferences
{
    public function add($key, callable $transformer = null, $default = null)
    {
        User::addPreference($key, $transformer, $default);
    }
}
