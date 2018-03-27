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

use Flarum\Core\Repository\PostRepository;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class ShowPostController extends AbstractResourceController
{
    /**
     * {@inheritdoc}
     */
    public $serializer = 'Flarum\Api\Serializer\PostSerializer';

    /**
     * {@inheritdoc}
     */
    public $include = [
        'user',
        'user.groups',
        'editUser',
        'hideUser',
        'discussion'
    ];

    /**
     * @var \Flarum\Core\Repository\PostRepository
     */
    protected $posts;

    /**
     * @param PostRepository $posts
     */
    public function __construct(PostRepository $posts)
    {
        $this->posts = $posts;
    }

    /**
     * {@inheritdoc}
     */
    protected function data(ServerRequestInterface $request, Document $document)
    {
        return $this->posts->findOrFail(array_get($request->getQueryParams(), 'id'), $request->getAttribute('actor'));
    }
}
