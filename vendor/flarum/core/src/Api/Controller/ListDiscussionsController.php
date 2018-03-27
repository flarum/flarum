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
use Flarum\Core\Search\Discussion\DiscussionSearcher;
use Flarum\Core\Search\SearchCriteria;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class ListDiscussionsController extends AbstractCollectionController
{
    /**
     * {@inheritdoc}
     */
    public $serializer = 'Flarum\Api\Serializer\DiscussionSerializer';

    /**
     * {@inheritdoc}
     */
    public $include = [
        'startUser',
        'lastUser',
        'relevantPosts',
        'relevantPosts.discussion',
        'relevantPosts.user'
    ];

    /**
     * {@inheritdoc}
     */
    public $optionalInclude = [
        'startPost',
        'lastPost'
    ];

    /**
     * {@inheritdoc}
     */
    public $sortFields = ['lastTime', 'commentsCount', 'startTime'];

    /**
     * @var DiscussionSearcher
     */
    protected $searcher;

    /**
     * @var UrlGenerator
     */
    protected $url;

    /**
     * @param DiscussionSearcher $searcher
     * @param UrlGenerator $url
     */
    public function __construct(DiscussionSearcher $searcher, UrlGenerator $url)
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
        $query = array_get($this->extractFilter($request), 'q');
        $sort = $this->extractSort($request);

        $criteria = new SearchCriteria($actor, $query, $sort);

        $limit = $this->extractLimit($request);
        $offset = $this->extractOffset($request);
        $load = array_merge($this->extractInclude($request), ['state']);

        $results = $this->searcher->search($criteria, $limit, $offset, $load);

        $document->addPaginationLinks(
            $this->url->toRoute('discussions.index'),
            $request->getQueryParams(),
            $offset,
            $limit,
            $results->areMoreResults() ? null : 0
        );

        return $results->getResults();
    }
}
