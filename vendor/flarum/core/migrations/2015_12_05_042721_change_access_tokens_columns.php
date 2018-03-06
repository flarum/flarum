<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

return [
    'up' => function (Builder $schema) {
        $schema->table('access_tokens', function (Blueprint $table) {
            $table->string('id', 40)->change();
            $table->dropColumn('created_at');
            $table->dropColumn('expires_at');
            $table->integer('last_activity');
            $table->integer('lifetime');
        });
    },

    'down' => function (Builder $schema) {
        $schema->table('access_tokens', function (Blueprint $table) {
            $table->string('id', 100)->change();
            $table->dropColumn('last_activity');
            $table->dropColumn('lifetime');
            $table->timestamp('created_at');
            $table->timestamp('expires_at');
        });
    }
];
