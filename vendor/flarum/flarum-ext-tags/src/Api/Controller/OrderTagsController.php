<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Tags\Api\Controller;

use Flarum\Core\Access\AssertPermissionTrait;
use Flarum\Http\Controller\ControllerInterface;
use Flarum\Tags\Tag;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\EmptyResponse;

class OrderTagsController implements ControllerInterface
{
    use AssertPermissionTrait;

    /**
     * {@inheritdoc}
     */
    public function handle(ServerRequestInterface $request)
    {
        $this->assertAdmin($request->getAttribute('actor'));

        $order = array_get($request->getParsedBody(), 'order');

        Tag::query()->update([
            'position' => null,
            'parent_id' => null
        ]);

        foreach ($order as $i => $parent) {
            $parentId = array_get($parent, 'id');

            Tag::where('id', $parentId)->update(['position' => $i]);

            if (isset($parent['children']) && is_array($parent['children'])) {
                foreach ($parent['children'] as $j => $childId) {
                    Tag::where('id', $childId)->update([
                        'position' => $j,
                        'parent_id' => $parentId
                    ]);
                }
            }
        }

        return new EmptyResponse(204);
    }
}
