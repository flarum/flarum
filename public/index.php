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

Flarum\Http\Server::fromSite(
    (new Flarum\Foundation\Site)
        ->setBasePath(__DIR__.'/..')
        ->setPublicPath(__DIR__)
)->listen();
