<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Core\Search\User;

use Flarum\Core\Repository\UserRepository;
use Flarum\Core\Search\ApplySearchParametersTrait;
use Flarum\Core\Search\GambitManager;
use Flarum\Core\Search\SearchCriteria;
use Flarum\Core\Search\SearchResults;
use Flarum\Event\ConfigureUserSearch;

/**
 * Takes a UserSearchCriteria object, performs a search using gambits,
 * and spits out a UserSearchResults object.
 */
class UserSearcher
{
    use ApplySearchParametersTrait;

    /**
     * @var GambitManager
     */
    protected $gambits;

    /**
     * @var UserRepository
     */
    protected $users;

    /**
     * @param GambitManager $gambits
     * @param UserRepository $users
     */
    public function __construct(GambitManager $gambits, UserRepository $users)
    {
        $this->gambits = $gambits;
        $this->users = $users;
    }

    /**
     * @param SearchCriteria $criteria
     * @param int|null $limit
     * @param int $offset
     * @param array $load An array of relationships to load on the results.
     * @return SearchResults
     */
    public function search(SearchCriteria $criteria, $limit = null, $offset = 0, array $load = [])
    {
        $actor = $criteria->actor;

        $query = $this->users->query()->whereVisibleTo($actor);

        // Construct an object which represents this search for users.
        // Apply gambits to it, sort, and paging criteria. Also give extensions
        // an opportunity to modify it.
        $search = new UserSearch($query->getQuery(), $actor);

        $this->gambits->apply($search, $criteria->query);
        $this->applySort($search, $criteria->sort);
        $this->applyOffset($search, $offset);
        $this->applyLimit($search, $limit + 1);

        event(new ConfigureUserSearch($search, $criteria));

        // Execute the search query and retrieve the results. We get one more
        // results than the user asked for, so that we can say if there are more
        // results. If there are, we will get rid of that extra result.
        $users = $query->get();

        if ($areMoreResults = ($limit > 0 && $users->count() > $limit)) {
            $users->pop();
        }

        $users->load($load);

        return new SearchResults($users, $areMoreResults);
    }
}
