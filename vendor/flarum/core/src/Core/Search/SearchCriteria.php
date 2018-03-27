<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Core\Search;

use Flarum\Core\User;

/**
 * Represents the criteria that will determine the entire result set of a
 * search. The limit and offset are not included because they only determine
 * which part of the entire result set will be returned.
 */
class SearchCriteria
{
    /**
     * The user performing the search.
     *
     * @var User
     */
    public $actor;

    /**
     * The search query.
     *
     * @var string
     */
    public $query;

    /**
     * An array of sort-order pairs, where the column is the key, and the order
     * is the value. The order may be 'asc', 'desc', or an array of IDs to
     * order by.
     *
     * @var array
     */
    public $sort;

    /**
     * @param User $actor The user performing the search.
     * @param string $query The search query.
     * @param array $sort An array of sort-order pairs, where the column is the
     *     key, and the order is the value. The order may be 'asc', 'desc', or
     *     an array of IDs to order by.
     */
    public function __construct(User $actor, $query, array $sort = null)
    {
        $this->actor = $actor;
        $this->query = $query;
        $this->sort = $sort;
    }
}
