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

use Flarum\Core\Command\PostReply;
use Flarum\Core\Command\ReadDiscussion;
use Flarum\Core\Post\Floodgate;
use Illuminate\Contracts\Bus\Dispatcher;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class CreatePostController extends AbstractCreateController
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
        'discussion',
        'discussion.posts',
        'discussion.lastUser'
    ];

    /**
     * @var Dispatcher
     */
    protected $bus;

    /**
     * @var Floodgate
     */
    protected $floodgate;

    /**
     * @param Dispatcher $bus
     * @param Floodgate $floodgate
     */
    public function __construct(Dispatcher $bus, Floodgate $floodgate)
    {
        $this->bus = $bus;
        $this->floodgate = $floodgate;
    }

    /**
     * {@inheritdoc}
     */
    protected function data(ServerRequestInterface $request, Document $document)
    {
        $actor = $request->getAttribute('actor');
        $data = array_get($request->getParsedBody(), 'data', []);
        $discussionId = array_get($data, 'relationships.discussion.data.id');
        $ipAddress = array_get($request->getServerParams(), 'REMOTE_ADDR', '127.0.0.1');

        if (! $request->getAttribute('bypassFloodgate')) {
            $this->floodgate->assertNotFlooding($actor);
        }

        $post = $this->bus->dispatch(
            new PostReply($discussionId, $actor, $data, $ipAddress)
        );

        // After replying, we assume that the user has seen all of the posts
        // in the discussion; thus, we will mark the discussion as read if
        // they are logged in.
        if ($actor->exists) {
            $this->bus->dispatch(
                new ReadDiscussion($discussionId, $actor, $post->number)
            );
        }

        $discussion = $post->discussion;
        $discussion->posts = $discussion->postsVisibleTo($actor)->orderBy('time')->lists('id');

        return $post;
    }
}
