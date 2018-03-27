<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Flarum\Approval\Access;
use Flarum\Approval\Listener;
use Illuminate\Contracts\Events\Dispatcher;

return function (Dispatcher $events) {
    $events->subscribe(Listener\AddClientAssets::class);
    $events->subscribe(Listener\AddPostApprovalAttributes::class);
    $events->subscribe(Listener\ApproveContent::class);
    $events->subscribe(Listener\HideUnapprovedContent::class);
    $events->subscribe(Listener\UnapproveNewContent::class);

    $events->subscribe(Access\TagPolicy::class);
};
