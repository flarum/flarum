<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Markdown\Listener;

use Flarum\Event\ConfigureFormatter;
use Illuminate\Contracts\Events\Dispatcher;

class FormatMarkdown
{
    /**
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events)
    {
        $events->listen(ConfigureFormatter::class, [$this, 'addMarkdownFormatter']);
    }

    /**
     * @param ConfigureFormatter $event
     */
    public function addMarkdownFormatter(ConfigureFormatter $event)
    {
        $event->configurator->Litedown;
    }
}
