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

class PrepareSerializedSetting
{
    /**
     * The settings key being saved.
     *
     * @var string
     */
    public $key;

    /**
     * The settings value to save.
     *
     * @var string
     */
    public $value;

    /**
     * @param string $key The settings key being saved.
     * @param string $value The settings value to save.
     */
    public function __construct($key, &$value)
    {
        $this->key = $key;
        $this->value = &$value;
    }
}
