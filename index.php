<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

// prevent all those questions concerning ::class and possible composer installation errors (5.3+)
if (PHP_VERSION_ID < 50509) {
    throw new Exception('At least PHP 5.5.9 is required to make Flarum work. See the system requirements: http://flarum.org/docs/installation/#system-requirements');
} elseif (file_exists('vendor/autoload.php')) {

    /**
     * Start up the Flarum installation wizard or the Forums.
     */
    require 'vendor/autoload.php';

    // delete /_install_tmp using composer functionality
    if (is_dir('_install_tmp') && file_exists('_install_tmp/composer/vendor/autoload.php')) {
        if (array_key_exists('install-done', $_GET)) {
            echo 1;
            exit;
        }
        // include the extracted composer libraries
        require_once '_install_tmp/composer/vendor/autoload.php';

        $fs = new Composer\Util\Filesystem();
        $fs->removeDirectory('_install_tmp');
    }

    $server = new Flarum\Forum\Server(__DIR__);

    $server->listen();

} elseif ((ini_get('allow_url_fopen') && !ini_get('phar.readonly')) || file_exists('scripts/composer.phar')) {
    if (array_key_exists('install-done', $_GET)) {
        echo 0;
        exit;
    }
    // prevent time out of page, drawback of running in browser
    @set_time_limit(0);
    // prevent extracting composer anew if already exists
    if (!is_dir('_install_tmp/composer')) {
        // attempt to download the latest composer file
        if (!file_exists('scripts/composer.phar') && ini_get('allow_url_fopen')) {
            file_put_contents('https://getcomposer.org/installer', 'scripts/composer.phar');
        }
        // use Phar to extract the composer package
        $composer = new Phar('scripts/composer.phar');
        // create a temporary directory for saving the package
        mkdir('_install_tmp');
        // extract composer
        $composer->extractTo('_install_tmp/composer');
    }
    // if extraction succeeded, let's run the update command.
    if (is_dir('_install_tmp/composer') && file_exists('_install_tmp/composer/vendor/autoload.php')) {
        // force memory to at least 1GB (default for composer) otherwise composer will run out of memory
        if (function_exists('ini_set')) {
            ini_set('memory_limit', '1G');
        }

        // show an installation html
        include "storage/views/composer-installation.html";
        // force the template to the user
        ob_flush();
        // sets a home directory for storing information
        putenv('COMPOSER_HOME=' . getcwd() . '/_install_tmp/home');
        // prevents any interaction composer might require
        putenv('COMPOSER_NO_INTERACTION=true');

        require_once '_install_tmp/composer/vendor/autoload.php';

        // run the composer installation command
        $application = new Composer\Console\Application();

        // disable auto exit
        $application->setAutoExit(false);

        // first set the github token to prevent installation errors
        $input = new \Symfony\Component\Console\Input\ArrayInput([
            'command'                 => 'config',
            'github-oauth.github.com' => 'ec785da935d5535e151f7b3386190265f00e8fe2'
        ]);
        $application->run($input);

        // set the input for the composer install command
        $input = new Symfony\Component\Console\Input\ArrayInput([
            'command'               => 'install',
            '--no-dev'              => true,
            '--prefer-dist'         => true,
            '--optimize-autoloader' => true,
            '-q'                    => true
        ]);
        $application->run($input);

        // application installation is now done, redirect to Flarum installer
        // we cannot send a header or redirect because we're already flushed the buffer
    }
} else {
    // todo provide proper error message
    throw new Exception('This method of installation is currently unsupported.');
}