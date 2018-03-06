<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Flarum\Database\Migration;
use Illuminate\Database\Schema\Blueprint;

return Migration::createTable(
    'users_discussions',
    function (Blueprint $table) {
        $table->integer('user_id')->unsigned();
        $table->integer('discussion_id')->unsigned();
        $table->dateTime('read_time')->nullable();
        $table->integer('read_number')->unsigned()->nullable();
        $table->primary(['user_id', 'discussion_id']);
    }
);
