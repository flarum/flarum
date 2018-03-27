<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Core\Search\User\Gambit;

use Flarum\Core\Repository\UserRepository;
use Flarum\Core\Search\AbstractRegexGambit;
use Flarum\Core\Search\AbstractSearch;
use Flarum\Core\Search\User\UserSearch;
use LogicException;

class EmailGambit extends AbstractRegexGambit
{
    /**
     * {@inheritdoc}
     */
    protected $pattern = 'email:(.+)';

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
    public function apply(AbstractSearch $search, $bit)
    {
        if (! $search->getActor()->isAdmin()) {
            return false;
        }

        return parent::apply($search, $bit);
    }

    /**
     * {@inheritdoc}
     */
    protected function conditions(AbstractSearch $search, array $matches, $negate)
    {
        if (! $search instanceof UserSearch) {
            throw new LogicException('This gambit can only be applied on a UserSearch');
        }

        $email = trim($matches[1], '"');

        $user = $this->users->findByEmail($email);

        $search->getQuery()->where('id', $negate ? '!=' : '=', $user->id);
    }
}
