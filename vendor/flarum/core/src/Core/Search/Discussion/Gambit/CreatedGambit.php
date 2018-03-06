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

use Flarum\Core\Search\AbstractRegexGambit;
use Flarum\Core\Search\AbstractSearch;
use Flarum\Core\Search\Discussion\DiscussionSearch;
use LogicException;

class CreatedGambit extends AbstractRegexGambit
{
    /**
     * {@inheritdoc}
     */
    protected $pattern = 'created:(\d{4}\-\d\d\-\d\d)(\.\.(\d{4}\-\d\d\-\d\d))?';

    /**
     * {@inheritdoc}
     */
    protected function conditions(AbstractSearch $search, array $matches, $negate)
    {
        if (! $search instanceof DiscussionSearch) {
            throw new LogicException('This gambit can only be applied on a DiscussionSearch');
        }

        // If we've just been provided with a single YYYY-MM-DD date, then find
        // discussions that were started on that exact date. But if we've been
        // provided with a YYYY-MM-DD..YYYY-MM-DD range, then find discussions
        // that were started during that period.
        if (empty($matches[3])) {
            $search->getQuery()->whereDate('start_time', $negate ? '!=' : '=', $matches[1]);
        } else {
            $search->getQuery()->whereBetween('start_time', [$matches[1], $matches[3]], 'and', $negate);
        }
    }
}
