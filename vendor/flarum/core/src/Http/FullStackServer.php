<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Http;

use Flarum\Admin\Server as AdminServer;
use Flarum\Api\Server as ApiServer;
use Flarum\Forum\Server as ForumServer;
use Flarum\Foundation\Application;
use Zend\Stratigility\MiddlewarePipe;

class FullStackServer extends AbstractServer
{
    /**
     * @param Application $app
     * @return \Zend\Stratigility\MiddlewareInterface
     */
    protected function getMiddleware(Application $app)
    {
        $pipe = new MiddlewarePipe;
        $pipe->raiseThrowables();

        $pipe->pipe(new ApiServer);
        $pipe->pipe(new AdminServer);
        $pipe->pipe(new ForumServer);

        return $pipe;
    }
}
