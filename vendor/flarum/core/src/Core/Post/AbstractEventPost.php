<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Core\Post;

use Flarum\Core\Post;

abstract class AbstractEventPost extends Post
{
    /**
     * Unserialize the content attribute from the database's JSON value.
     *
     * @param string $value
     * @return string
     */
    public function getContentAttribute($value)
    {
        return json_decode($value, true);
    }

    /**
     * Serialize the content attribute to be stored in the database as JSON.
     *
     * @param string $value
     */
    public function setContentAttribute($value)
    {
        $this->attributes['content'] = json_encode($value);
    }
}
