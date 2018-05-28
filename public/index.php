<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require '../vendor/autoload.php';

$site = Flarum\Foundation\Site::fromPaths([
    'base' => __DIR__.'/..',
    'public' => __DIR__,
]);

$app = $site->bootApp();

$server = new Flarum\Http\Server(
    $app->getRequestHandler()
);

$server->listen();
