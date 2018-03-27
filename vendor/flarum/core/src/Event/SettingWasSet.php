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

class SettingWasSet
{
    /**
     * The setting key that was set.
     *
     * @var string
     */
    public $key;

    /**
     * The setting value that was set.
     *
     * @var string
     */
    public $value;

    /**
     * @param string $key The setting key that was set.
     * @param string $value The setting value that was set.
     */
    public function __construct($key, $value)
    {
        $this->key = $key;
        $this->value = $value;
    }
}
