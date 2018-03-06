<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Settings;

interface SettingsRepositoryInterface
{
    public function all();

    public function get($key, $default = null);

    public function set($key, $value);

    public function delete($keyLike);
}
