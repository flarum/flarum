<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Core\Access;

use Flarum\Event\GetPermission;
use Flarum\Event\ScopeModelVisibility;
use Illuminate\Contracts\Events\Dispatcher;

abstract class AbstractPolicy
{
    /**
     * @var string
     */
    protected $model;

    /**
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events)
    {
        $events->listen(GetPermission::class, [$this, 'getPermission']);
        $events->listen(GetPermission::class, [$this, 'getPermissionAfter'], -100);
        $events->listen(ScopeModelVisibility::class, [$this, 'scopeModelVisibility']);
    }

    /**
     * @param GetPermission $event
     * @return bool|null
     */
    public function getPermission(GetPermission $event)
    {
        if ($event->model instanceof $this->model && method_exists($this, $event->ability)) {
            return call_user_func_array([$this, $event->ability], [$event->actor, $event->model]);
        }
    }

    /**
     * @param GetPermission $event
     * @return bool|null
     */
    public function getPermissionAfter(GetPermission $event)
    {
        if ($event->model instanceof $this->model && method_exists($this, 'after')) {
            return call_user_func_array([$this, 'after'], [$event->actor, $event->ability, $event->model]);
        }
    }

    /**
     * @param ScopeModelVisibility $event
     */
    public function scopeModelVisibility(ScopeModelVisibility $event)
    {
        if ($event->model instanceof $this->model && method_exists($this, 'find')) {
            call_user_func_array([$this, 'find'], [$event->actor, $event->query]);
        }
    }
}
