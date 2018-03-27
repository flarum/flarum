<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Core\Search\Discussion\Gambit;

use Flarum\Core\Search\AbstractSearch;
use Flarum\Core\Search\Discussion\DiscussionSearch;
use Flarum\Core\Search\Discussion\Fulltext\DriverInterface;
use Flarum\Core\Search\GambitInterface;
use LogicException;

class FulltextGambit implements GambitInterface
{
    /**
     * @var DriverInterface
     */
    protected $fulltext;

    /**
     * @param DriverInterface $fulltext
     */
    public function __construct(DriverInterface $fulltext)
    {
        $this->fulltext = $fulltext;
    }

    /**
     * {@inheritdoc}
     */
    public function apply(AbstractSearch $search, $bit)
    {
        if (! $search instanceof DiscussionSearch) {
            throw new LogicException('This gambit can only be applied on a DiscussionSearch');
        }

        $relevantPostIds = $this->fulltext->match($bit);

        $discussionIds = array_keys($relevantPostIds);

        $search->setRelevantPostIds($relevantPostIds);

        $search->getQuery()->whereIn('id', $discussionIds);

        $search->setDefaultSort(['id' => $discussionIds]);
    }
}
