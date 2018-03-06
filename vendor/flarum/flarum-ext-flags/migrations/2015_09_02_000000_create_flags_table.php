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
    'flags',
    function (Blueprint $table) {
        $table->increments('id');
        $table->integer('post_id')->unsigned();
        $table->string('type');
        $table->integer('user_id')->unsigned()->nullable();
        $table->string('reason')->nullable();
        $table->string('reason_detail')->nullable();
        $table->dateTime('time');
    }
);
