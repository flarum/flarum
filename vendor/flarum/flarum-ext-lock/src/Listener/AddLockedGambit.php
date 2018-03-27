<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Lock\Listener;

use Flarum\Event\ConfigureDiscussionGambits;
use Flarum\Lock\Gambit\LockedGambit;
use Illuminate\Contracts\Events\Dispatcher;

class AddLockedGambit
{
    /**
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events)
    {
        $events->listen(ConfigureDiscussionGambits::class, [$this, 'configureDiscussionGambits']);
    }

    /**
     * @param ConfigureDiscussionGambits $event
     */
    public function configureDiscussionGambits(ConfigureDiscussionGambits $event)
    {
        $event->gambits->add(LockedGambit::class);
    }
}
