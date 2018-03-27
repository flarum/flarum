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
    'notifications',
    function (Blueprint $table) {
        $table->increments('id');
        $table->integer('user_id')->unsigned();
        $table->integer('sender_id')->unsigned()->nullable();
        $table->string('type', 100);
        $table->string('subject_type', 200)->nullable();
        $table->integer('subject_id')->unsigned()->nullable();
        $table->binary('data')->nullable();
        $table->dateTime('time');
        $table->boolean('is_read')->default(0);
        $table->boolean('is_deleted')->default(0);
    }
);
