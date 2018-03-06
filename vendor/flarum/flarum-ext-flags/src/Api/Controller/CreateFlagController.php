<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Flags\Api\Controller;

use Flarum\Api\Controller\AbstractCreateController;
use Flarum\Flags\Api\Serializer\FlagSerializer;
use Flarum\Flags\Command\CreateFlag;
use Illuminate\Contracts\Bus\Dispatcher;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class CreateFlagController extends AbstractCreateController
{
    /**
     * {@inheritdoc}
     */
    public $serializer = FlagSerializer::class;

    /**
     * {@inheritdoc}
     */
    public $include = [
        'post',
        'post.flags'
    ];

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
        return $this->bus->dispatch(
            new CreateFlag($request->getAttribute('actor'), array_get($request->getParsedBody(), 'data', []))
        );
    }
}
