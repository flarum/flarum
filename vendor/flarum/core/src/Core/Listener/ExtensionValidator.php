<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Core\Listener;

use Flarum\Event\ExtensionWillBeDisabled;
use Flarum\Http\Exception\ForbiddenException;
use Illuminate\Contracts\Events\Dispatcher;

class ExtensionValidator
{
    /**
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events)
    {
        $events->listen(ExtensionWillBeDisabled::class, [$this, 'whenExtensionWillBeDisabled']);
    }

    /**
     * @param ExtensionWillBeDisabled $event
     * @throws ForbiddenException
     */
    public function whenExtensionWillBeDisabled(ExtensionWillBeDisabled $event)
    {
        if (in_array('flarum-locale', $event->extension->extra)) {
            $default_locale = $this->app->make('flarum.settings')->get('default_locale');
            $locale = array_get($event->extension->extra, 'flarum-locale.code');
            if ($locale === $default_locale) {
                throw new ForbiddenException('You cannot disable the default language pack!');
            }
        }
    }
}
