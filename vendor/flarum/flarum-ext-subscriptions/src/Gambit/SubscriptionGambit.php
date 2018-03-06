<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Subscriptions\Gambit;

use Flarum\Core\Search\AbstractRegexGambit;
use Flarum\Core\Search\AbstractSearch;
use Illuminate\Database\Query\Expression;

class SubscriptionGambit extends AbstractRegexGambit
{
    /**
     * {@inheritdoc}
     */
    protected $pattern = 'is:(follow|ignor)(?:ing|ed)';

    /**
     * {@inheritdoc}
     */
    protected function conditions(AbstractSearch $search, array $matches, $negate)
    {
        $actor = $search->getActor();

        // might be better as `id IN (subquery)`?
        $method = $negate ? 'whereNotExists' : 'whereExists';
        $search->getQuery()->$method(function ($query) use ($actor, $matches) {
            $query->select(app('flarum.db')->raw(1))
                  ->from('users_discussions')
                  ->where('discussions.id', new Expression('discussion_id'))
                  ->where('user_id', $actor->id)
                  ->where('subscription', $matches[1] === 'follow' ? 'follow' : 'ignore');
        });
    }
}
