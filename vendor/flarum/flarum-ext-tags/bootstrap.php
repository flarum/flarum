<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Flarum\Tags\Access;
use Flarum\Tags\Listener;
use Illuminate\Contracts\Events\Dispatcher;

return function (Dispatcher $events) {
    $events->subscribe(Listener\AddClientAssets::class);
    $events->subscribe(Listener\AddDiscussionTagsRelationship::class);
    $events->subscribe(Listener\AddForumTagsRelationship::class);
    $events->subscribe(Listener\AddTagsApi::class);
    $events->subscribe(Listener\CreatePostWhenTagsAreChanged::class);
    $events->subscribe(Listener\FilterDiscussionListByTags::class);
    $events->subscribe(Listener\FilterPostsQueryByTag::class);
    $events->subscribe(Listener\SaveTagsToDatabase::class);
    $events->subscribe(Listener\UpdateTagMetadata::class);

    $events->subscribe(Access\GlobalPolicy::class);
    $events->subscribe(Access\DiscussionPolicy::class);
    $events->subscribe(Access\TagPolicy::class);
    $events->subscribe(Access\FlagPolicy::class);
};
