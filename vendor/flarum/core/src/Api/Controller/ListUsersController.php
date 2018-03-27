<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Api\Controller;

use Flarum\Api\UrlGenerator;
use Flarum\Core\Exception\PermissionDeniedException;
use Flarum\Core\Search\SearchCriteria;
use Flarum\Core\Search\User\UserSearcher;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class ListUsersController extends AbstractCollectionController
{
    /**
     * {@inheritdoc}
     */
    public $serializer = 'Flarum\Api\Serializer\UserSerializer';

    /**
     * {@inheritdoc}
     */
    public $include = ['groups'];

    /**
     * {@inheritdoc}
     */
    public $sortFields = [
        'username',
        'commentsCount',
        'discussionsCount',
        'lastSeenTime',
        'joinTime'
    ];

    /**
     * @var UserSearcher
     */
    protected $searcher;

    /**
     * @var UrlGenerator
     */
    protected $url;

    /**
     * @param UserSearcher $searcher
     * @param UrlGenerator $url
     */
    public function __construct(UserSearcher $searcher, UrlGenerator $url)
    {
        $this->searcher = $searcher;
        $this->url = $url;
    }

    /**
     * {@inheritdoc}
     */
    protected function data(ServerRequestInterface $request, Document $document)
    {
        $actor = $request->getAttribute('actor');

        if ($actor->cannot('viewUserList')) {
            throw new PermissionDeniedException;
        }

        $query = array_get($this->extractFilter($request), 'q');
        $sort = $this->extractSort($request);

        $criteria = new SearchCriteria($actor, $query, $sort);

        $limit = $this->extractLimit($request);
        $offset = $this->extractOffset($request);
        $load = $this->extractInclude($request);

        $results = $this->searcher->search($criteria, $limit, $offset, $load);

        $document->addPaginationLinks(
            $this->url->toRoute('users.index'),
            $request->getQueryParams(),
            $offset,
            $limit,
            $results->areMoreResults() ? null : 0
        );

        return $results->getResults();
    }
}
