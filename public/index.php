<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require '../flarum/vendor/autoload.php';

$server = new Flarum\Forum\Server(__DIR__, __DIR__ . '/../flarum');

$server->listen();
