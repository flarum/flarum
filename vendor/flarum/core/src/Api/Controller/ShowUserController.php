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

use Flarum\Core\Repository\UserRepository;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class ShowUserController extends AbstractResourceController
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
    protected function data(ServerRequestInterface $request, Document $document)
    {
        $id = array_get($request->getQueryParams(), 'id');

        if (! is_numeric($id)) {
            $id = $this->users->getIdForUsername($id);
        }

        $actor = $request->getAttribute('actor');

        if ($actor->id == $id) {
            $this->serializer = 'Flarum\Api\Serializer\CurrentUserSerializer';
        }

        return $this->users->findOrFail($id, $actor);
    }
}
