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

use Illuminate\Database\Eloquent\Builder;

class ConfigurePostsQuery
{
    /**
     * @var Builder
     */
    public $query;

    /**
     * @var array
     */
    public $filter;

    /**
     * @param Builder $query
     * @param array $filter
     */
    public function __construct(Builder $query, array $filter)
    {
        $this->query = $query;
        $this->filter = $filter;
    }
}
