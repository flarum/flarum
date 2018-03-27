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

use Psr\Http\Message\ServerRequestInterface;

abstract class AbstractCreateController extends AbstractResourceController
{
    /**
     * {@inheritdoc}
     */
    public function handle(ServerRequestInterface $request)
    {
        return parent::handle($request)->withStatus(201);
    }
}
