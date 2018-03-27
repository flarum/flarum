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
use Flarum\Core\Search\AbstractSearch;
use Flarum\Core\Search\GambitInterface;

class FulltextGambit implements GambitInterface
{
    /**
     * @var UserRepository
     */
    protected $users;

    /**
     * @param UserRepository $users
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
        $users = $this->users->getIdsForUsername($bit, $search->getActor());

        $search->getQuery()->whereIn('id', $users);

        $search->setDefaultSort(['id' => $users]);
    }
}
