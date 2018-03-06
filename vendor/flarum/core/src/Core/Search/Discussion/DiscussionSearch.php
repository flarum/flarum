<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Core\Search\Discussion;

use Flarum\Core\Search\AbstractSearch;

/**
 * An object which represents the internal state of a search for discussions:
 * the search query, the user performing the search, the fallback sort order,
 * relevant post information, and a log of which gambits have been used.
 */
class DiscussionSearch extends AbstractSearch
{
    /**
     * {@inheritdoc}
     */
    protected $defaultSort = ['lastTime' => 'desc'];

    /**
     * @var array
     */
    protected $relevantPostIds = [];

    /**
     * Get the related IDs for each result.
     *
     * @return int[]
     */
    public function getRelevantPostIds()
    {
        return $this->relevantPostIds;
    }

    /**
     * Set the relevant post IDs for the results.
     *
     * @param array $relevantPostIds
     * @return void
     */
    public function setRelevantPostIds(array $relevantPostIds)
    {
        $this->relevantPostIds = $relevantPostIds;
    }
}
