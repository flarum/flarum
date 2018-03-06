<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Forum\Controller;

use Flarum\Core\Exception\PermissionDeniedException;
use Psr\Http\Message\ServerRequestInterface as Request;

class AuthorizedWebAppController extends WebAppController
{
    /**
     * {@inheritdoc}
     */
    public function render(Request $request)
    {
        if (! $request->getAttribute('session')->get('user_id')) {
            throw new PermissionDeniedException;
        }

        return parent::render($request);
    }
}
