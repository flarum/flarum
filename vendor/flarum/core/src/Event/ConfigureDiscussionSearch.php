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

use Flarum\Core\Search\Discussion\DiscussionSearch;
use Flarum\Core\Search\SearchCriteria;

class ConfigureDiscussionSearch
{
    /**
     * @var DiscussionSearch
     */
    public $search;

    /**
     * @var SearchCriteria
     */
    public $criteria;

    /**
     * @param DiscussionSearch $search
     * @param SearchCriteria $criteria
     */
    public function __construct(DiscussionSearch $search, SearchCriteria $criteria)
    {
        $this->search = $search;
        $this->criteria = $criteria;
    }
}
