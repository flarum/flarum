<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Flarum\Subscriptions\Listener;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\View\Factory;

return function (Dispatcher $events, Factory $views) {
    $events->subscribe(Listener\AddClientAssets::class);
    $events->subscribe(Listener\AddDiscussionSubscriptionAttribute::class);
    $events->subscribe(Listener\FilterDiscussionListBySubscription::class);
    $events->subscribe(Listener\SaveSubscriptionToDatabase::class);
    $events->subscribe(Listener\SendNotificationWhenReplyIsPosted::class);
    $events->subscribe(Listener\FollowAfterReply::class);

    $views->addNamespace('flarum-subscriptions', __DIR__.'/views');
};
