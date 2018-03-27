<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Core\Repository;

use Flarum\Core\Notification;
use Flarum\Core\User;

class NotificationRepository
{
    /**
     * Find a user's notifications.
     *
     * @param User $user
     * @param int|null $limit
     * @param int $offset
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function findByUser(User $user, $limit = null, $offset = 0)
    {
        $primaries = Notification::select(
            app('flarum.db')->raw('MAX(id) AS id'),
            app('flarum.db')->raw('SUM(is_read = 0) AS unread_count')
        )
            ->where('user_id', $user->id)
            ->whereIn('type', $user->getAlertableNotificationTypes())
            ->where('is_deleted', false)
            ->groupBy('type', 'subject_id')
            ->orderByRaw('MAX(time) DESC')
            ->skip($offset)
            ->take($limit);

        return Notification::select('notifications.*', app('flarum.db')->raw('p.unread_count'))
            ->mergeBindings($primaries->getQuery())
            ->join(app('flarum.db')->raw('('.$primaries->toSql().') p'), 'notifications.id', '=', app('flarum.db')->raw('p.id'))
            ->latest('time')
            ->get();
    }

    /**
     * Mark all of a user's notifications as read.
     *
     * @param User $user
     *
     * @return void
     */
    public function markAllAsRead(User $user)
    {
        Notification::where('user_id', $user->id)->update(['is_read' => true]);
    }
}
