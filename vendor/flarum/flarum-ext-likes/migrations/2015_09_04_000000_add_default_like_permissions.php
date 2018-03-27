<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Illuminate\Database\ConnectionInterface;

$permissionAttributes = [
    'group_id' => 3, // Default group ID of members
    'permission' => 'discussion.likePosts',
];

return [
    'up' => function (ConnectionInterface $db) use ($permissionAttributes) {
        $instance = $db->table('permissions')->where($permissionAttributes)->first();

        if (is_null($instance)) {
            $db->table('permissions')->insert($permissionAttributes);
        }
    },

    'down' => function (ConnectionInterface $db) use ($permissionAttributes) {
        $db->table('permissions')->where($permissionAttributes)->delete();
    }
];
