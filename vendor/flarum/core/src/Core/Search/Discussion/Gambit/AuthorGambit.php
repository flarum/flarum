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

use Flarum\Core\Repository\UserRepository;
use Flarum\Core\Search\AbstractRegexGambit;
use Flarum\Core\Search\AbstractSearch;
use Flarum\Core\Search\Discussion\DiscussionSearch;
use LogicException;

class AuthorGambit extends AbstractRegexGambit
{
    /**
     * {@inheritdoc}
     */
    protected $pattern = 'author:(.+)';

    /**
     * @var UserRepository
     */
    protected $users;

    /**
     * @param \Flarum\Core\Repository\UserRepository $users
     */
    public function __construct(UserRepository $users)
    {
        $this->users = $users;
    }

    /**
     * {@inheritdoc}
     */
    protected function conditions(AbstractSearch $search, array $matches, $negate)
    {
        if (! $search instanceof DiscussionSearch) {
            throw new LogicException('This gambit can only be applied on a DiscussionSearch');
        }

        $usernames = trim($matches[1], '"');
        $usernames = explode(',', $usernames);

        $ids = [];
        foreach ($usernames as $username) {
            $ids[] = $this->users->getIdForUsername($username);
        }

        $search->getQuery()->whereIn('start_user_id', $ids, 'and', $negate);
    }
}
