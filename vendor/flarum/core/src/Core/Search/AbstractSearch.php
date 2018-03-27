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
use Illuminate\Database\Query\Builder;

/**
 * An object which represents the internal state of a generic search:
 * the search query, the user performing the search, the fallback sort order,
 * and a log of which gambits have been used.
 */
abstract class AbstractSearch
{
    /**
     * @var Builder
     */
    protected $query;

    /**
     * @var User
     */
    protected $actor;

    /**
     * @var array
     */
    protected $defaultSort = [];

    /**
     * @var GambitInterface[]
     */
    protected $activeGambits = [];

    /**
     * @param Builder $query
     * @param User $actor
     */
    public function __construct(Builder $query, User $actor)
    {
        $this->query = $query;
        $this->actor = $actor;
    }

    /**
     * Get the query builder for the search results query.
     *
     * @return Builder
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * Get the user who is performing the search.
     *
     * @return User
     */
    public function getActor()
    {
        return $this->actor;
    }

    /**
     * Get the default sort order for the search.
     *
     * @return array
     */
    public function getDefaultSort()
    {
        return $this->defaultSort;
    }

    /**
     * Set the default sort order for the search. This will only be applied if
     * a sort order has not been specified in the search criteria.
     *
     * @param array $defaultSort An array of sort-order pairs, where the column
     *     is the key, and the order is the value. The order may be 'asc',
     *     'desc', or an array of IDs to order by.
     * @return mixed
     */
    public function setDefaultSort(array $defaultSort)
    {
        $this->defaultSort = $defaultSort;
    }

    /**
     * Get a list of the gambits that are active in this search.
     *
     * @return GambitInterface[]
     */
    public function getActiveGambits()
    {
        return $this->activeGambits;
    }

    /**
     * Add a gambit as being active in this search.
     *
     * @param GambitInterface $gambit
     * @return void
     */
    public function addActiveGambit(GambitInterface $gambit)
    {
        $this->activeGambits[] = $gambit;
    }
}
