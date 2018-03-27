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
    'mentions_posts',
    function (Blueprint $table) {
        $table->integer('post_id')->unsigned();
        $table->integer('mentions_id')->unsigned();
        $table->primary(['post_id', 'mentions_id']);
    }
);
