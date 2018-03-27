<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Admin\Middleware;

use Flarum\Core\Access\AssertPermissionTrait;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Zend\Stratigility\MiddlewareInterface;

class RequireAdministrateAbility implements MiddlewareInterface
{
    use AssertPermissionTrait;

    /**
     * {@inheritdoc}
     */
    public function __invoke(Request $request, Response $response, callable $out = null)
    {
        $this->assertAdmin($request->getAttribute('actor'));

        return $out ? $out($request, $response) : $response;
    }
}
