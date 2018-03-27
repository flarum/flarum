<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Core\Search\Discussion\Fulltext;

interface DriverInterface
{
    /**
     * Return an array of arrays of post IDs, grouped by discussion ID, which
     * match the given string.
     *
     * @param string $string
     * @return array
     */
    public function match($string);
}
