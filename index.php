<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

if (file_exists('vendor/autoload.php')) {
    require_once 'vendor/autoload.php';

    (new Flarum\Forum\Server(__DIR__))->listen();

} elseif (file_exists('install/index.php')) {
    $url = 'http://' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/') . '/install/index.php';
    header("Location:{$url}", true, 301);
    exit;
}