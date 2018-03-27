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
    'groups',
    function (Blueprint $table) {
        $table->increments('id');
        $table->string('name_singular', 100);
        $table->string('name_plural', 100);
        $table->string('color', 20)->nullable();
        $table->string('icon', 100)->nullable();
    }
);
