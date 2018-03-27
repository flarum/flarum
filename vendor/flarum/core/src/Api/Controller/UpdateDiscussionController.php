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

use Flarum\Core\Command\EditDiscussion;
use Flarum\Core\Command\ReadDiscussion;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Database\Eloquent\Collection;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class UpdateDiscussionController extends AbstractResourceController
{
    /**
     * {@inheritdoc}
     */
    public $serializer = 'Flarum\Api\Serializer\DiscussionSerializer';

    /**
     * @var Dispatcher
     */
    protected $bus;

    /**
     * @param Dispatcher $bus
     */
    public function __construct(Dispatcher $bus)
    {
        $this->bus = $bus;
    }

    /**
     * {@inheritdoc}
     */
    protected function data(ServerRequestInterface $request, Document $document)
    {
        $actor = $request->getAttribute('actor');
        $discussionId = array_get($request->getQueryParams(), 'id');
        $data = array_get($request->getParsedBody(), 'data', []);

        $discussion = $this->bus->dispatch(
            new EditDiscussion($discussionId, $actor, $data)
        );

        // TODO: Refactor the ReadDiscussion (state) command into EditDiscussion?
        // That's what extensions will do anyway.
        if ($readNumber = array_get($data, 'attributes.readNumber')) {
            $state = $this->bus->dispatch(
                new ReadDiscussion($discussionId, $actor, $readNumber)
            );

            $discussion = $state->discussion;
        }

        if ($posts = $discussion->getModifiedPosts()) {
            $posts = (new Collection($posts))->load('discussion', 'user');
            $discussionPosts = $discussion->postsVisibleTo($actor)->orderBy('time')->lists('id')->all();

            foreach ($discussionPosts as &$id) {
                foreach ($posts as $post) {
                    if ($id == $post->id) {
                        $id = $post;
                    }
                }
            }

            $discussion->setRelation('posts', $discussionPosts);

            $this->include = array_merge($this->include, ['posts', 'posts.discussion', 'posts.user']);
        }

        return $discussion;
    }
}
