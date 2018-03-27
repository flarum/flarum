<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

return [
    'up' => function (Builder $schema) {
        $schema->create('{{table}}', function (Blueprint $table) {
            $table->increments('id');
        });
    },

    'down' => function (Builder $schema) {
        $schema->drop('{{table}}');
    }
];
