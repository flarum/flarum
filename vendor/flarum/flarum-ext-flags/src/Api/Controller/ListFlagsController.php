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

use Flarum\Api\Controller\AbstractCollectionController;
use Flarum\Flags\Api\Serializer\FlagSerializer;
use Flarum\Flags\Flag;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class ListFlagsController extends AbstractCollectionController
{
    /**
     * {@inheritdoc}
     */
    public $serializer = FlagSerializer::class;

    /**
     * {@inheritdoc}
     */
    public $include = [
        'user',
        'post',
        'post.user',
        'post.discussion'
    ];

    /**
     * {@inheritdoc}
     */
    protected function data(ServerRequestInterface $request, Document $document)
    {
        $actor = $request->getAttribute('actor');

        $actor->flags_read_time = time();
        $actor->save();

        return Flag::whereVisibleTo($actor)
            ->with($this->extractInclude($request))
            ->latest('flags.time')
            ->groupBy('post_id')
            ->get();
    }
}
