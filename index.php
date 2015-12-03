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
    require 'vendor/autoload.php';

    $server = new Flarum\Forum\Server(__DIR__);

    $server->listen();
} elseif (ini_get('allow_url_fopen') || file_exists('scripts/composer.phar')) {
    // prevent extracting composer anew if already exists
    if (!is_dir('tmp/composer')) {
        // attempt to download the latest composer file
        if(ini_get('allow_url_fopen'))
        {
            file_put_contents('https://getcomposer.org/installer', 'scripts/composer.phar');
        }
        $composer = new Phar('scripts/composer.phar');
        mkdir('tmp');
        $composer->extractTo('tmp/composer');
    }
    // if extraction succeeded, let's run the update command.
    if (is_dir('tmp/composer') && file_exists('tmp/composer/vendor/autoload.php')) {
        // otherwise composer will run out of memory easily
        ini_set('memory_limit', '-1');
        // include the extracted composer libraries
        require_once 'tmp/composer/vendor/autoload.php';

        putenv('COMPOSER_HOME=' . getcwd() . '/tmp/home');

        // set the input for the composer command
        $input = new Symfony\Component\Console\Input\ArrayInput(['command' => 'install', '--no-dev']);

        // run the composer things
        $application = new Composer\Console\Application();
        $application->run($input);
    }
} else {
    throw new Exception('This method of installation is currently unsupported.');
}