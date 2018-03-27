<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Console;

use Flarum\Console\Command\GenerateMigrationCommand;
use Flarum\Debug\Console\CacheClearCommand;
use Flarum\Debug\Console\InfoCommand;
use Flarum\Foundation\AbstractServer;
use Flarum\Install\Console\InstallCommand;
use Flarum\Update\Console\MigrateCommand;
use Symfony\Component\Console\Application;

class Server extends AbstractServer
{
    public function listen()
    {
        $console = $this->getConsoleApplication();

        exit($console->run());
    }

    /**
     * @return Application
     */
    protected function getConsoleApplication()
    {
        $app = $this->getApp();
        $console = new Application('Flarum', $app->version());

        $app->register('Flarum\Install\InstallServiceProvider');

        $commands = [
            InstallCommand::class,
            MigrateCommand::class,
            GenerateMigrationCommand::class,
        ];

        if ($app->isInstalled()) {
            $commands = array_merge($commands, [
                InfoCommand::class,
                CacheClearCommand::class
            ]);
        }

        foreach ($commands as $command) {
            $console->add($app->make(
                $command,
                ['config' => $app->isInstalled() ? $app->make('flarum.config') : []]
            ));
        }

        return $console;
    }
}
