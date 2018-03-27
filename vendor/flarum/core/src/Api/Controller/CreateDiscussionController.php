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

use Flarum\Core\Command\ReadDiscussion;
use Flarum\Core\Command\StartDiscussion;
use Flarum\Core\Post\Floodgate;
use Illuminate\Contracts\Bus\Dispatcher;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class CreateDiscussionController extends AbstractCreateController
{
    /**
     * {@inheritdoc}
     */
    public $serializer = 'Flarum\Api\Serializer\DiscussionSerializer';

    /**
     * {@inheritdoc}
     */
    public $include = [
        'posts',
        'startUser',
        'lastUser',
        'startPost',
        'lastPost'
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
        $ipAddress = array_get($request->getServerParams(), 'REMOTE_ADDR', '127.0.0.1');

        if (! $request->getAttribute('bypassFloodgate')) {
            $this->floodgate->assertNotFlooding($actor);
        }

        $discussion = $this->bus->dispatch(
            new StartDiscussion($actor, array_get($request->getParsedBody(), 'data', []), $ipAddress)
        );

        // After creating the discussion, we assume that the user has seen all
        // of the posts in the discussion; thus, we will mark the discussion
        // as read if they are logged in.
        if ($actor->exists) {
            $this->bus->dispatch(
                new ReadDiscussion($discussion->id, $actor, 1)
            );
        }

        return $discussion;
    }
}
