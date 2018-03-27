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
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

return [
    'up' => function (Builder $schema, ConnectionInterface $db) {
        $schema->create('tags', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 100);
            $table->string('slug', 100);
            $table->text('description')->nullable();

            $table->string('color', 50)->nullable();
            $table->string('background_path', 100)->nullable();
            $table->string('background_mode', 100)->nullable();

            $table->integer('position')->nullable();
            $table->integer('parent_id')->unsigned()->nullable();
            $table->string('default_sort', 50)->nullable();
            $table->boolean('is_restricted')->default(0);
            $table->boolean('is_hidden')->default(0);

            $table->integer('discussions_count')->unsigned()->default(0);
            $table->dateTime('last_time')->nullable();
            $table->integer('last_discussion_id')->unsigned()->nullable();
        });

        $db->table('tags')->insert([
            'name' => 'General',
            'slug' => 'general',
            'color' => '#888',
            'position' => '0'
        ]);
    },

    'down' => function (Builder $schema) {
        $schema->drop('tags');
    }
];
