<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Event;

use Flarum\Admin\Server as AdminServer;
use Flarum\Api\Server as ApiServer;
use Flarum\Forum\Server as ForumServer;
use Flarum\Foundation\AbstractServer;
use Zend\Stratigility\MiddlewarePipe;

class ConfigureMiddleware
{
    /**
     * @var MiddlewarePipe
     */
    public $pipe;

    /**
     * @var string
     */
    public $path;

    /**
     * @var AbstractServer
     */
    public $server;

    /**
     * @param MiddlewarePipe $pipe
     * @param string $path
     * @param AbstractServer $server
     */
    public function __construct(MiddlewarePipe $pipe, $path, AbstractServer $server)
    {
        $this->pipe = $pipe;
        $this->path = $path;
        $this->server = $server;
    }

    public function pipe(callable $middleware)
    {
        $this->pipe->pipe($this->path, $middleware);
    }

    public function isForum()
    {
        return $this->server instanceof ForumServer;
    }

    public function isAdmin()
    {
        return $this->server instanceof AdminServer;
    }

    public function isApi()
    {
        return $this->server instanceof ApiServer;
    }
}
