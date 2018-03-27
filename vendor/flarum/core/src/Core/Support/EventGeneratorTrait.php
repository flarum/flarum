<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Core\Support;

trait EventGeneratorTrait
{
    /**
     * @var array
     */
    protected $pendingEvents = [];

    /**
     * Raise a new event.
     *
     * @param mixed $event
     */
    public function raise($event)
    {
        $this->pendingEvents[] = $event;
    }

    /**
     * Return and reset all pending events.
     *
     * @return array
     */
    public function releaseEvents()
    {
        $events = $this->pendingEvents;

        $this->pendingEvents = [];

        return $events;
    }
}
