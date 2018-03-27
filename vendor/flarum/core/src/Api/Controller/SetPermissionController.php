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

use Flarum\Core\Access\AssertPermissionTrait;
use Flarum\Core\Permission;
use Flarum\Http\Controller\ControllerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\EmptyResponse;

class SetPermissionController implements ControllerInterface
{
    use AssertPermissionTrait;

    /**
     * {@inheritdoc}
     */
    public function handle(ServerRequestInterface $request)
    {
        $this->assertAdmin($request->getAttribute('actor'));

        $body = $request->getParsedBody();
        $permission = array_get($body, 'permission');
        $groupIds = array_get($body, 'groupIds');

        Permission::where('permission', $permission)->delete();

        Permission::insert(array_map(function ($groupId) use ($permission) {
            return [
                'permission' => $permission,
                'group_id' => $groupId
            ];
        }, $groupIds));

        return new EmptyResponse(204);
    }
}
