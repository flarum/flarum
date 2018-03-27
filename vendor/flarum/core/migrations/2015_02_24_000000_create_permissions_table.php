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
    'permissions',
    function (Blueprint $table) {
        $table->integer('group_id')->unsigned();
        $table->string('permission', 100);
        $table->primary(['group_id', 'permission']);
    }
);
